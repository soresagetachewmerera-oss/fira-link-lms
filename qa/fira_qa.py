#!/usr/bin/env python3
"""
Fira Link launch QA run: drives the real server over HTTP the way the app and the web do, and
reports PASS / FAIL / SKIP for each check. Standard library only.

    python qa/fira_qa.py --base-url http://127.0.0.1:8000
    python qa/fira_qa.py --base-url https://staging.example.com --artisan "ssh deploy@staging cd /var/www/app && php artisan"

What it needs from the server (see docs/qa/QA_TEST_PLAN.md):
  * APP_ENV is not production; on a non-local server QA_TOOLS_ENABLED=true.
  * `php artisan qa:seed / qa:code / qa:confirm-payment / qa:cleanup` reachable through --artisan.

It creates its own accounts (<run>.<role>@qa.firalink.test), never touches real accounts, and
removes everything it created at the end (unless --keep). Passwords and tokens are never printed.
"""
from __future__ import annotations

import argparse
import base64
import datetime as dt
import hashlib
import hmac
import http.cookiejar
import json
import os
import re
import shlex
import struct
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import uuid
import zlib

HERE = os.path.dirname(os.path.abspath(__file__))
DEFAULT_APP = os.path.normpath(os.path.join(HERE, '..', 'firalink web', 'firalink'))
NEW_PASSWORD = 'QaReset#2026New'


# ── Small helpers ─────────────────────────────────────────────────────────────

def png_bytes(size: int = 400) -> bytes:
    """A real PNG of random noise (passes the server's real-file checks)."""
    raw = bytearray()
    for _ in range(size):
        raw.append(0)
        raw += os.urandom(size * 3)

    def chunk(kind: bytes, data: bytes) -> bytes:
        return struct.pack('>I', len(data)) + kind + data + struct.pack('>I', zlib.crc32(kind + data) & 0xFFFFFFFF)

    return b'\x89PNG\r\n\x1a\n' + chunk(b'IHDR', struct.pack('>IIBBBBB', size, size, 8, 2, 0, 0, 0)) + chunk(b'IDAT', zlib.compress(bytes(raw), 6)) + chunk(b'IEND', b'')


def totp(secret: str, at: float | None = None) -> str:
    key = base64.b32decode(secret.upper() + '=' * (-len(secret) % 8))
    counter = int((at or time.time()) // 30)
    digest = hmac.new(key, struct.pack('>Q', counter), hashlib.sha1).digest()
    offset = digest[-1] & 0x0F
    return str((struct.unpack('>I', digest[offset:offset + 4])[0] & 0x7FFFFFFF) % 1_000_000).zfill(6)


class RateLimiter:
    """Stays under the server's per-minute limits (API auth 10/min, web sign-in 6/min)."""

    def __init__(self):
        self.calls: dict[str, list[float]] = {}

    def wait(self, bucket: str, per_minute: int) -> None:
        now = time.time()
        recent = [t for t in self.calls.get(bucket, []) if now - t < 61]
        if len(recent) >= per_minute - 1:
            pause = 61 - (now - recent[0]) + 1
            print(f'    (waiting {int(pause)}s for the {bucket} rate limit)')
            time.sleep(max(pause, 0))
            recent = [t for t in recent if time.time() - t < 61]
        recent.append(time.time())
        self.calls[bucket] = recent


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


# ── HTTP ──────────────────────────────────────────────────────────────────────

class Client:
    def __init__(self, base: str, limiter: RateLimiter):
        self.base = base.rstrip('/')
        self.limiter = limiter
        self.api_opener = urllib.request.build_opener(NoRedirect)

    def _bucket(self, method: str, path: str) -> None:
        if method == 'POST' and re.match(r'^/api/(auth|instructor/auth)/', path):
            self.limiter.wait('api-auth', 10)

    def api(self, method: str, path: str, token: str | None = None, data: dict | None = None,
            form: dict | None = None, files: dict | None = None) -> tuple[int, dict]:
        self._bucket(method, path)
        headers = {'Accept': 'application/json', 'User-Agent': 'FiraLinkQA/1.0'}
        if token:
            headers['Authorization'] = 'Bearer ' + token
        body = None
        if files or form:
            boundary = uuid.uuid4().hex
            parts = []
            for k, v in (form or {}).items():
                if v is None:
                    continue
                parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode())
            for k, (name, content, ctype) in (files or {}).items():
                parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"; filename="{name}"\r\nContent-Type: {ctype}\r\n\r\n'.encode() + content + b'\r\n')
            parts.append(f'--{boundary}--\r\n'.encode())
            body = b''.join(parts)
            headers['Content-Type'] = f'multipart/form-data; boundary={boundary}'
        elif data is not None:
            body = json.dumps(data).encode()
            headers['Content-Type'] = 'application/json'
        req = urllib.request.Request(self.base + path, data=body, method=method, headers=headers)
        try:
            with self.api_opener.open(req, timeout=60) as r:
                code, raw = r.status, r.read()
        except urllib.error.HTTPError as e:
            code, raw = e.code, e.read()
        except urllib.error.URLError as e:
            return 0, {'error': str(e.reason)}
        try:
            return code, json.loads(raw or b'{}')
        except ValueError:
            return code, {'_text': raw[:300].decode('utf-8', 'replace')}


class WebSession:
    """A browser-like session: cookies, CSRF token from the form, redirects not followed."""

    def __init__(self, base: str, limiter: RateLimiter):
        self.base = base.rstrip('/')
        self.limiter = limiter
        self.jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar), NoRedirect)

    def request(self, method: str, path: str, form: dict | None = None, limited: bool = True) -> tuple[int, str, str]:
        if limited and method == 'POST' and path in ('/login', '/login/otp', '/forgot-password', '/staff/two-factor'):
            self.limiter.wait('web-sign-in', 6)
        body = urllib.parse.urlencode(form).encode() if form is not None else None
        req = urllib.request.Request(self.base + path, data=body, method=method, headers={'User-Agent': 'FiraLinkQA/1.0', 'Accept': 'text/html'})
        if body:
            req.add_header('Content-Type', 'application/x-www-form-urlencoded')
        try:
            with self.opener.open(req, timeout=60) as r:
                return r.status, r.headers.get('Location', ''), r.read().decode('utf-8', 'replace')
        except urllib.error.HTTPError as e:
            return e.code, e.headers.get('Location', ''), e.read().decode('utf-8', 'replace')
        except urllib.error.URLError as e:
            return 0, '', str(e.reason)

    def csrf(self, path: str) -> str:
        _, _, html = self.request('GET', path)
        m = re.search(r'name="_token"\s+value="([^"]+)"', html) or re.search(r'<meta name="csrf-token" content="([^"]+)"', html)
        return m.group(1) if m else ''

    def post_form(self, path: str, page: str, fields: dict) -> tuple[int, str, str]:
        return self.request('POST', path, {'_token': self.csrf(page), **fields})


# ── Run ───────────────────────────────────────────────────────────────────────

class QaRun:
    def __init__(self, args):
        self.args = args
        self.limiter = RateLimiter()
        self.http = Client(args.base_url, self.limiter)
        self.results: list[tuple[str, str, str, str]] = []
        self.suite = ''
        self.seed: dict = {}
        self.tokens: dict[str, str] = {}
        self.png = png_bytes()

    # results
    def check(self, name: str, ok, detail='') -> bool:
        status = 'PASS' if ok else 'FAIL'
        self.results.append((self.suite, name, status, '' if ok else str(detail)[:300]))
        print(f'  {status}  {name}' + ('' if ok else f'\n        -> {str(detail)[:300]}'))
        return bool(ok)

    def skip(self, name: str, why: str) -> None:
        self.results.append((self.suite, name, 'SKIP', why))
        print(f'  SKIP  {name}  ({why})')

    # server side QA commands
    def artisan(self, *args: str) -> str:
        cmd = shlex.split(self.args.artisan) + list(args)
        cwd = self.args.app_dir if os.path.isdir(self.args.app_dir) else None
        r = subprocess.run(cmd, cwd=cwd, capture_output=True, text=True, stdin=subprocess.DEVNULL, timeout=300)
        out = (r.stdout or '') + (r.stderr or '')
        if r.returncode != 0:
            raise RuntimeError(f"artisan {' '.join(args[:1])} failed: {out.strip()[-400:]}")
        return out

    def code(self, identifier, purpose: str) -> str:
        out = self.artisan('qa:code', str(identifier), purpose)
        m = re.search(r'QA_CODE:(\d+)', out)
        if not m:
            raise RuntimeError('no code: ' + out[-200:])
        return m.group(1)

    def acc(self, key: str) -> dict:
        return self.seed['accounts'][key]

    def sign_in(self, key: str, password: str | None = None) -> str | None:
        """API sign-in with email and password; finishes an email code step when the server asks."""
        a = self.acc(key)
        c, b = self.http.api('POST', '/api/auth/login', data={'email': a['email'], 'password': password or self.seed['password']})
        if c == 200 and b.get('otp_required'):
            code = self.code(a['id'], 'login')
            c, b = self.http.api('POST', '/api/auth/login/otp/verify', data={'email': a['email'], 'otp': code, 'code': code})
        return b.get('token') if c == 200 else None

    def token(self, key: str) -> str:
        if key not in self.tokens:
            t = self.sign_in(key)
            if not t:
                raise RuntimeError(f'could not sign in as {key}')
            self.tokens[key] = t
        return self.tokens[key]

    def step_up(self, key: str) -> bool:
        c, _ = self.http.api('POST', '/api/auth/step-up/verify', self.token(key), data={'password': self.seed['password']})
        return c == 200

    def web_staff_session(self, key: str) -> tuple[WebSession, list[str]]:
        """Web sign-in as a staff account: password, email code if asked, then the authenticator code."""
        s = WebSession(self.args.base_url, self.limiter)
        a = self.acc(key)
        trail = []
        c, loc, _ = s.post_form('/login', '/login', {'email': a['email'], 'password': self.seed['password']})
        trail.append(f'login {c} {loc}')
        if '/login/otp' in loc:
            code = self.code(a['id'], 'login')
            c, loc, _ = s.post_form('/login/otp', '/login/otp', {'code': code})
            trail.append(f'otp {c} {loc}')
        c, loc, _ = s.request('GET', '/admin/verification-review')
        trail.append(f'queue {c} {loc}')
        if 'two-factor' in loc:
            secret = self.seed['staff_totp_secrets'][key]
            # Wait for the rate limit first: a code made before a long wait would expire.
            self.limiter.wait('web-sign-in', 6)
            token = s.csrf('/staff/two-factor')
            if time.time() % 30 > 25:  # too close to the next 30 second window
                time.sleep(31 - time.time() % 30)
            c, loc, _ = s.request('POST', '/staff/two-factor', {'_token': token, 'code': totp(secret)}, limited=False)
            trail.append(f'2fa {c} {loc}')
        return s, trail

    # ── Suites ────────────────────────────────────────────────────────────────

    def suite_health(self):
        c, b = self.http.api('GET', '/api/settings')
        self.check('API answers (GET /api/settings)', c == 200, c)
        for path in ['/', '/login', '/forgot-password', '/events', '/tours']:
            c, _, _ = WebSession(self.args.base_url, self.limiter).request('GET', path)
            self.check(f'web page {path} loads', c == 200, c)
        fira = self.seed['businesses']['exporter']['fira_id']
        c, _, html = WebSession(self.args.base_url, self.limiter).request('GET', f'/verify/{fira}')
        self.check('public seller page /verify/<Fira ID> shows the verified business', c == 200 and self.seed['businesses']['exporter']['name'] in html, c)
        c, b = self.http.api('GET', f'/api/sellers/{fira}')
        self.check('public seller check API by Fira ID', c == 200, c)

    def suite_auth(self):
        st = self.acc('student')
        c, b = self.http.api('POST', '/api/auth/login', data={'email': st['email'], 'password': 'Wrong-password-1'})
        wrong = (c, b.get('code'))
        self.check('wrong password is refused', c in (401, 422) and b.get('code') == 'INVALID_CREDENTIALS', wrong)
        c, b = self.http.api('POST', '/api/auth/login', data={'email': f"{self.seed['run']}.nobody@qa.firalink.test", 'password': 'Wrong-password-1'})
        self.check('unknown email gets the same answer (no account guessing)', (c, b.get('code')) == wrong, (c, b.get('code')))
        c, b = self.http.api('GET', '/api/user')
        self.check('signed-out request is refused (401)', c == 401, c)

        for key in ['student', 'instructor', 'admin', 'exporter_owner', 'buyer_owner']:
            t = self.sign_in(key)
            if not self.check(f'sign in with email and password: {key}', t, 'no token'):
                continue
            self.tokens[key] = t
            c, b = self.http.api('GET', '/api/user', t)
            u = b.get('data', b) if isinstance(b.get('data', b), dict) else b
            role = (u.get('user') or u).get('role') if isinstance(u, dict) else None
            self.check(f'GET /api/user shows role {self.acc(key)["role"]}: {key}', c == 200 and role == self.acc(key)['role'], (c, role))

        # Sign out ends the token.
        t = self.sign_in('student')
        c, _ = self.http.api('POST', '/api/logout', t)
        c2, _ = self.http.api('GET', '/api/user', t)
        self.check('sign out ends the token', c == 200 and c2 == 401, (c, c2))

        # New account by email with a code.
        email = f"{self.seed['run']}.signup@qa.firalink.test"
        c, b = self.http.api('POST', '/api/auth/email/register/start', data={'full_name': 'QA Signup', 'email': email, 'password': 'QaSignup#2026', 'password_confirmation': 'QaSignup#2026', 'terms': True})
        if self.check('email sign-up sends a code', c == 200, (c, b)):
            code = self.code(email, 'email_signup')
            c, b = self.http.api('POST', '/api/auth/email/register/verify', data={'email': email, 'code': '000000'})
            self.check('a wrong sign-up code is refused', c in (401, 422), c)
            c, b = self.http.api('POST', '/api/auth/email/register/verify', data={'email': email, 'code': code})
            self.check('the right code creates the account and signs in', c in (200, 201) and b.get('token'), (c, b.get('status'), b.get('code')))
        c, b = self.http.api('POST', '/api/auth/email/register/start', data={'full_name': 'QA Weak', 'email': f"{self.seed['run']}.weak@qa.firalink.test", 'password': 'short', 'password_confirmation': 'short', 'terms': True})
        self.check('a weak password is refused at sign-up', c == 422, c)

        # Forgot password by email, end to end.
        old = self.token('exporter_staff')
        c, b = self.http.api('POST', '/api/auth/password/forgot', data={'channel': 'email', 'email': f"{self.seed['run']}.nobody@qa.firalink.test"})
        unknown = (c, b.get('message'))
        staff = self.acc('exporter_staff')
        c, b = self.http.api('POST', '/api/auth/password/forgot', data={'channel': 'email', 'email': staff['email']})
        self.check('forgot password: same answer for known and unknown emails', (c, b.get('message')) == unknown and c == 200, ((c, b.get('message')), unknown))
        try:
            code = self.code(staff['email'], 'password_reset')
        except RuntimeError as e:
            self.check('forgot password sends a code', False, e)
        else:
            c, b = self.http.api('POST', '/api/auth/password/verify-code', data={'channel': 'email', 'email': staff['email'], 'code': code})
            ticket = b.get('reset_ticket')
            self.check('the reset code gives a reset ticket', c == 200 and ticket, (c, b.get('code')))
            c, b = self.http.api('POST', '/api/auth/password/reset', data={'reset_ticket': ticket, 'password': 'weakpass', 'password_confirmation': 'weakpass'})
            self.check('a weak new password is refused', c == 422, c)
            c, b = self.http.api('POST', '/api/auth/password/reset', data={'reset_ticket': ticket, 'password': NEW_PASSWORD, 'password_confirmation': NEW_PASSWORD})
            self.check('the new password is saved', c == 200, (c, b))
            c, b = self.http.api('POST', '/api/auth/password/reset', data={'reset_ticket': ticket, 'password': NEW_PASSWORD, 'password_confirmation': NEW_PASSWORD})
            self.check('the reset ticket works only once', c == 422, c)
            self.check('the old password no longer works', self.sign_in('exporter_staff') is None, 'still signs in')
            self.check('the new password works', self.sign_in('exporter_staff', NEW_PASSWORD) is not None, 'no token')
            c, _ = self.http.api('GET', '/api/user', old)
            self.check('the reset signs out other devices', c == 401, c)
            self.tokens.pop('exporter_staff', None)
            self.tokens['exporter_staff'] = self.sign_in('exporter_staff', NEW_PASSWORD) or ''

        # Step-up for sensitive actions.
        c, _ = self.http.api('POST', '/api/auth/step-up/verify', self.token('buyer_owner'), data={'password': 'Wrong-password-1'})
        self.check('step-up: a wrong password is refused', c in (401, 403, 422), c)
        self.check('step-up: the password confirms it is you', self.step_up('buyer_owner'), 'refused')

        # Phone sign-in needs a SIM you own ("seed" = a QA account's number; only with the local test code).
        if self.args.phone == 'seed':
            self.args.phone = self.acc('student')['phone']
        if not self.args.phone:
            self.skip('phone sign-in with an SMS code', 'pass --phone with a QA SIM listed in QA_PHONES')
        else:
            c, b = self.http.api('POST', '/api/auth/phone/start', data={'phone': self.args.phone, 'country_code': '+251'})
            if c == 503:
                self.skip('phone sign-in with an SMS code', 'SMS is not configured on this server')
            elif self.check('phone sign-in sends a code', c == 200, (c, b.get('code'))):
                code = self.code(self.args.phone, 'phone_signin')
                c, b = self.http.api('POST', '/api/auth/phone/verify', data={'phone': self.args.phone, 'code': code})
                if b.get('status') == 'new_user':
                    c, b = self.http.api('POST', '/api/auth/phone/complete', data={'signup_ticket': b.get('signup_ticket'), 'full_name': 'QA Phone User'})
                self.check('the SMS code signs in (or creates the account)', c in (200, 201) and b.get('token'), (c, b.get('status'), b.get('code')))

    def suite_roles(self):
        c, _ = self.http.api('GET', '/api/instructor/dashboard', self.token('student'))
        self.check('student cannot open the instructor API', c in (401, 403), c)
        c, _ = self.http.api('GET', '/api/instructor/dashboard', self.token('instructor'))
        self.check('approved instructor opens the instructor API', c == 200, c)
        c, b = self.http.api('GET', '/api/instructor/dashboard', self.token('instructor_pending'))
        self.check('instructor waiting for approval is told it is pending', c == 403 and b.get('is_approved') is False, (c, b))

        # Business roles inside one business.
        self.step_up('exporter_owner')
        c, b = self.http.api('POST', '/api/payout-account', self.token('exporter_manager'), data={'bank_name': 'CBE', 'account_name': 'QA', 'account_number': '1000111122223333'})
        self.check('business manager cannot change the payout bank account', c == 403, (c, b.get('code')))
        c, _ = self.http.api('GET', '/api/payout-account', self.token('exporter_staff'))
        self.check('business staff cannot see the payout bank account', c == 403, c)
        c, b = self.http.api('GET', '/api/payout-account', self.token('exporter_owner'))
        self.check('business owner sees the payout account masked', c == 200 and b['data']['account'] and len(b['data']['account']['account_last4']) == 4, (c, b))
        c, b = self.http.api('GET', '/api/marketplace/verification/status', self.token('exporter_manager'))
        self.check('manager can manage verification documents', c == 200 and b.get('data', {}).get('can_manage') is True, (c, b.get('data', {}).get('can_manage')))
        c, b = self.http.api('GET', '/api/marketplace/verification/status', self.token('exporter_staff'))
        self.check('staff can view verification but not manage it', c == 200 and b.get('data', {}).get('can_manage') is False, (c, b.get('data', {}).get('can_manage')))
        c, _ = self.http.api('GET', '/api/marketplace/seller/home', self.token('exporter_owner'))
        self.check('seller home for a seller business', c == 200, c)
        c, b = self.http.api('GET', '/api/marketplace/seller/home', self.token('buyer_owner'))
        self.check('no seller home for a buyer business', c == 403, (c, b.get('code')))

    def suite_web(self):
        s = WebSession(self.args.base_url, self.limiter)
        c, loc, _ = s.request('GET', '/admin/dashboard')
        self.check('admin needs sign-in (redirect to /login)', c in (301, 302) and '/login' in loc, (c, loc))
        st = self.acc('student')
        c, loc, _ = s.post_form('/login', '/login', {'email': st['email'], 'password': 'Wrong-password-1'})
        self.check('web: wrong password goes back to the form', c in (301, 302) and '/login' in loc and '/admin' not in loc, (c, loc))
        c, loc, _ = s.post_form('/login', '/login', {'email': st['email'], 'password': self.seed['password']})
        if '/login/otp' in loc:
            c, loc, _ = s.post_form('/login/otp', '/login/otp', {'code': self.code(st['id'], 'login')})
        self.check('web: student signs in', c in (301, 302) and '/login' not in loc, (c, loc))
        c, loc, _ = s.request('GET', '/admin/dashboard')
        self.check('web: a student cannot open admin', c in (302, 403) and '/admin/dashboard' not in loc, (c, loc))

        admin, trail = self.web_staff_session('admin')
        self.check('web: admin needs the authenticator code, then gets in', any(t.startswith('2fa') for t in trail), trail)
        for path, name in [('/admin/dashboard', 'admin dashboard'), ('/admin/verification-review', 'verification review queue'),
                           ('/admin/verification-review?tab=reports', 'reports tab'), ('/admin/verification-review?tab=payouts', 'payout accounts tab'),
                           ('/admin/payments/offline-pending', 'bank slips (offline payments)'),
                           (f"/admin/events/{self.seed['content']['event']}", 'event details'), (f"/admin/tours/{self.seed['content']['tour']}", 'tour details')]:
            c, loc, _ = admin.request('GET', path)
            self.check(f'web admin: {name} loads', c == 200, (c, loc))

        reviewer, trail = self.web_staff_session('reviewer')
        c, loc, _ = reviewer.request('GET', '/admin/verification-review')
        self.check('web: reviewer opens the review queue after the authenticator code', c == 200, (c, loc, trail))
        c, loc, _ = reviewer.request('GET', '/admin/dashboard')
        self.check('web: reviewer cannot open the admin dashboard', c in (302, 403), (c, loc))

        f = WebSession(self.args.base_url, self.limiter)
        c, b = self.http.api('POST', '/api/instructor/auth/forgot-password', data={'email': self.acc('instructor')['email']})
        c2, b2 = self.http.api('POST', '/api/instructor/auth/forgot-password', data={'email': f"{self.seed['run']}.nobody@qa.firalink.test"})
        self.check('instructor forgot password: same answer for known and unknown emails', (c, b.get('message')) == (c2, b2.get('message')) and c == 200, ((c, b), (c2, b2)))
        c1, l1, _ = f.post_form('/forgot-password', '/forgot-password', {'email': st['email']})
        c2, l2, _ = f.post_form('/forgot-password', '/forgot-password', {'email': f"{self.seed['run']}.nobody@qa.firalink.test"})
        self.check('web forgot password answers the same for known and unknown emails', (c1, l1) == (c2, l2) and c1 in (200, 302), ((c1, l1), (c2, l2)))
        c, loc, _ = s.request('POST', '/logout', {'_token': s.csrf('/student/dashboard')})
        c, loc, _ = s.request('GET', '/student/dashboard')
        self.check('web: sign out ends the session', c in (301, 302) and '/login' in loc, (c, loc))

    def suite_trade(self):
        buyer, seller = self.token('buyer_owner'), self.token('exporter_owner')
        lot, hidden = self.seed['content']['lot'], self.seed['content']['hidden_lot']
        c, b = self.http.api('GET', '/api/marketplace/listings?search=QA&per_page=50')
        ids = [x.get('id') for x in (b.get('data', {}).get('data') if isinstance(b.get('data'), dict) else b.get('data') or [])]
        self.check('marketplace shows the verified seller lot', c == 200 and lot in ids, (c, ids[:10]))
        self.check('marketplace hides a lot of an unverified business', hidden not in ids, ids[:10])

        c, b = self.http.api('POST', '/api/trade-chats', buyer, data={'seller_organization_id': self.seed['businesses']['exporter']['id'], 'listing_id': lot})
        cid = (b.get('data') or {}).get('id')
        self.check('buyer messages the seller (business chat)', c in (200, 201) and cid, (c, b.get('code')))
        c, b = self.http.api('POST', f'/api/trade-chats/{cid}/messages', seller, data={'body': 'Faster on WhatsApp: +251 911 234 567'})
        mid = (b.get('data') or {}).get('id')
        self.check('a message with contact details is delivered and flagged', c == 201 and b['data']['flagged'], (c, b))
        c, b = self.http.api('GET', f'/api/trade-chats/{cid}', buyer)
        msgs = (b.get('data') or {}).get('messages') or []
        self.check('the buyer sees the warning box', any(m.get('show_warning') for m in msgs), msgs[-1:])
        self.check('chat shows businesses, never people', 'QA Exporter Owner' not in json.dumps(b) and '@qa.firalink.test' not in json.dumps(b), 'person shown')
        c, b = self.http.api('POST', '/api/reports', buyer, data={'reason_code': 'pay_outside', 'reported_message_id': mid, 'block': True})
        self.check('report this message and block the business', c == 201 and b['data']['blocked'], (c, b))
        c, b = self.http.api('POST', f'/api/trade-chats/{cid}/messages', seller, data={'body': 'hello'})
        self.check('a blocked business cannot message', c == 403 and b.get('code') == 'BLOCKED', (c, b.get('code')))
        c, _ = self.http.api('DELETE', f"/api/business-blocks/{self.seed['businesses']['exporter']['id']}", buyer)
        self.check('unblock', c == 200, c)

        c, b = self.http.api('POST', '/api/marketplace/samples', buyer, data={
            'listing_id': lot, 'quantity_grams': 250, 'delivery_company': 'QA Roasters', 'delivery_address': 'Street 1',
            'delivery_country': 'Germany', 'delivery_phone': '+49 40 1234567', 'courier_carrier': 'dhl', 'courier_account': '963123456'})
        self.check('buyer requests a free sample', c == 201, (c, b.get('code')))

        c, b = self.http.api('POST', '/api/deal-rooms', buyer, data={'listing_id': lot, 'title': 'QA deal', 'target_value_usd': 100000, 'incoterm': 'FOB'})
        deal = (b.get('data') or {}).get('id')
        if self.check('buyer opens a deal room', c == 201 and deal, (c, b.get('code'))):
            for who, t in (('buyer', buyer), ('seller', seller)):
                c, _ = self.http.api('POST', f'/api/deal-rooms/{deal}/sign-contract', t, data={'signature': 'QA'})
                self.check(f'{who} signs the contract', c == 200, c)
            c, b = self.http.api('GET', f'/api/deal-rooms/{deal}', buyer)
            d = b.get('data') or {}
            self.check('deal room: step 2 of 6, waiting for the deposit', d.get('step_number') == 2 and d.get('step_count') == 6 and d.get('current_step') == 'payment_escrow', (d.get('step_number'), d.get('current_step')))
            self.check('deal room: pay to the confirmed account, masked', (d.get('pay_to') or {}).get('state') == 'ready' and 'account_number' not in json.dumps(d.get('pay_to')), d.get('pay_to'))
            c, b = self.http.api('POST', f"/api/deal-rooms/{deal}/milestones/{d['milestones'][2]['id']}/advance", seller, form={'notes': 'x'}, files={'document': ('r.png', self.png, 'image/png')})
            self.check('a later step is locked until the bank confirms the one before', c == 422 and b.get('code') == 'STEP_LOCKED', (c, b.get('code')))
            c, b = self.http.api('POST', f"/api/deal-rooms/{deal}/milestones/{d['milestones'][1]['id']}/advance", buyer, form={'payment_method': 'bank_transfer'}, files={'document': ('slip.png', self.png, 'image/png')})
            self.check('buyer sends the deposit slip (waits for the bank)', c == 200 and (b.get('data') or {}).get('status') == 'proof_submitted', (c, b.get('code')))
        c, _ = self.http.api('GET', '/api/deal-rooms', buyer)
        self.check('deals list', c == 200, c)

    def suite_events_tours(self):
        buyer = self.token('buyer_owner')
        ev, tour, tdate = self.seed['content']['event'], self.seed['content']['tour'], self.seed['content']['tour_date']
        c, b = self.http.api('GET', f'/api/events/{ev}', buyer)
        self.check('event: organizer verified, agenda, tickets', c == 200 and b['flow']['organizer']['verified'] and b['flow']['agenda'], (c, b.get('flow')))
        form = {'first_name': 'QA', 'last_name': 'Buyer', 'phone': '+251911000000', 'seats': '2', 'payment_method': 'bank_transfer', 'company': 'QA Roasters'}
        c, b = self.http.api('POST', f'/api/events/{ev}/book', buyer, form=form, files={'slip': ('fake.png', b'\0' * 20480, 'image/png')})
        self.check('a fake slip file is refused', c == 422 and b.get('code') == 'FILE_REJECTED', (c, b.get('code')))
        c, b = self.http.api('POST', f'/api/events/{ev}/book', buyer, form=form, files={'slip': ('slip.png', self.png, 'image/png')})
        bid = b.get('booking_id')
        self.check('ticket by bank slip: In review, total with service fee', c == 200 and b['booking']['in_review'] and b['booking']['total_amount'] == 1050.0, (c, b.get('booking')))
        c, b = self.http.api('GET', f'/api/user/bookings/{bid}', buyer)
        self.check('no QR code while in review', c == 200 and b.get('qr_code') is None, c)
        pid = self.payment_for('event_booking_id', bid)
        if pid:
            self.artisan('qa:confirm-payment', str(pid))
            c, b = self.http.api('GET', f'/api/user/bookings/{bid}', buyer)
            self.check('after finance confirms: ticket confirmed with a QR code', b.get('ticket', {}).get('status') == 'confirmed' and (b.get('qr_code') or '').startswith('data:image'), b.get('ticket', {}).get('status'))

        c, b = self.http.api('GET', f'/api/tours/{tour}')
        self.check('tour: host verified, next dates, deposit rule', c == 200 and b['host']['verified'] and b['next_dates'] and b['deposit_percent'] == 30, (c, b.get('host')))
        tf = {'first_name': 'QA', 'last_name': 'Guest', 'phone': '+251911000001', 'participants': '2', 'schedule_date': tdate, 'payment_method': 'bank_transfer'}
        c, _ = self.http.api('POST', f'/api/tours/{tour}/book', None, form=tf, files={'slip': ('slip.png', self.png, 'image/png')})
        self.check('tour booking needs sign-in', c == 401, c)
        c, b = self.http.api('POST', f'/api/tours/{tour}/book', buyer, form=tf, files={'slip': ('slip.png', self.png, 'image/png')})
        tb = b.get('booking_id')
        self.check('tour: 30% deposit now, balance later', c == 200 and b['booking']['deposit_amount'] == 6000.0 and b['booking']['balance_amount'] == 14000.0, (c, b.get('booking')))
        c, _ = self.http.api('POST', f'/api/user/tour-bookings/{tb}/pay-balance', buyer, form={'payment_method': 'bank_transfer'}, files={'slip': ('slip.png', self.png, 'image/png')})
        self.check('balance cannot be paid before the deposit is confirmed', c == 403, c)
        pid = self.payment_for('tour_booking_id', tb, 'tour_deposit')
        if pid:
            self.artisan('qa:confirm-payment', str(pid))
            c, b = self.http.api('POST', f'/api/user/tour-bookings/{tb}/pay-balance', buyer, form={'payment_method': 'bank_transfer'}, files={'slip': ('slip.png', self.png, 'image/png')})
            self.check('balance slip sent after the deposit', c == 200, (c, b.get('code')))
            pid2 = self.payment_for('tour_booking_id', tb, 'tour_balance')
            if pid2:
                self.artisan('qa:confirm-payment', str(pid2))
            c, b = self.http.api('GET', f'/api/user/tour-bookings/{tb}', buyer)
            self.check('tour fully paid after finance confirms the balance', b.get('ticket', {}).get('payment_status') == 'paid', b.get('ticket', {}).get('payment_status'))

    def payment_for(self, column: str, booking_id, purpose: str | None = None) -> int | None:
        """Finds the QA payment id through the server (read-only tinker)."""
        where = f"->where('purpose', '{purpose}')" if purpose else ''
        try:
            out = self.artisan('tinker', '--execute', f"echo 'QA_PAY:' . App\\Models\\Payment::where('{column}', {int(booking_id)}){where}->latest('id')->value('id');")
        except RuntimeError:
            return None
        m = re.search(r'QA_PAY:(\d+)', out)
        return int(m.group(1)) if m else None

    # ── Main ──────────────────────────────────────────────────────────────────

    def run(self) -> int:
        print(f'Fira Link QA run against {self.args.base_url}')
        try:
            out = self.artisan('qa:seed', *(['--password', self.args.password] if self.args.password else []))
        except Exception as e:
            print(f'Could not create QA data: {e}')
            return 2
        m = re.search(r'QA_JSON:(\{.*\})', out)
        if not m:
            print('qa:seed gave no data:\n' + out[-500:])
            return 2
        self.seed = json.loads(m.group(1))
        print(f"QA run {self.seed['run']} ({len(self.seed['accounts'])} accounts)\n")
        suites = [s.strip() for s in self.args.suites.split(',') if s.strip()]
        try:
            for name in suites:
                fn = getattr(self, f'suite_{name}', None)
                if not fn:
                    print(f'Unknown suite {name}')
                    continue
                self.suite = name
                print(f'[{name}]')
                try:
                    fn()
                except Exception as e:  # a crash in one suite is a failure, the run continues
                    self.check(f'{name} suite finished', False, repr(e))
                print()
        finally:
            if self.args.keep:
                print(f"Kept QA run {self.seed['run']}. Remove it later: php artisan qa:cleanup --run={self.seed['run']}")
            else:
                try:
                    self.artisan('qa:cleanup', f"--run={self.seed['run']}")
                    print(f"Removed QA run {self.seed['run']}.")
                except Exception as e:
                    print(f'Cleanup failed: {e}')
        return self.report()

    def report(self) -> int:
        counts = {k: sum(1 for r in self.results if r[2] == k) for k in ('PASS', 'FAIL', 'SKIP')}
        print(f"PASS {counts['PASS']}   FAIL {counts['FAIL']}   SKIP {counts['SKIP']}")
        os.makedirs(self.args.report_dir, exist_ok=True)
        stamp = dt.datetime.now().strftime('%Y%m%d-%H%M')
        path = os.path.join(self.args.report_dir, f'qa-report-{stamp}.md')
        with open(path, 'w', encoding='utf-8') as f:
            f.write(f"# Fira Link QA report {stamp}\n\nServer: {self.args.base_url}  \nRun: {self.seed.get('run', '-')}  \n")
            f.write(f"Result: **{counts['PASS']} passed, {counts['FAIL']} failed, {counts['SKIP']} skipped**\n\n")
            f.write('| Suite | Check | Result | Detail |\n|---|---|---|---|\n')
            for suite, name, status, detail in self.results:
                f.write(f"| {suite} | {name} | {status} | {detail.replace('|', '/')} |\n")
        print(f'Report: {path}')
        return 1 if counts['FAIL'] else 0


def main() -> int:
    p = argparse.ArgumentParser(description='Fira Link launch QA run (HTTP end to end).')
    p.add_argument('--base-url', default=os.environ.get('QA_BASE_URL', 'http://127.0.0.1:8000'))
    p.add_argument('--artisan', default=os.environ.get('QA_ARTISAN', 'php artisan'), help='How to run artisan on the server (local or over ssh).')
    p.add_argument('--app-dir', default=os.environ.get('QA_APP_DIR', DEFAULT_APP), help='Laravel folder for a local --artisan.')
    p.add_argument('--suites', default='health,auth,roles,web,trade,events_tours')
    p.add_argument('--phone', default=os.environ.get('QA_PHONE'), help='A QA SIM you own (also in QA_PHONES on the server) for phone sign-in; "seed" uses a QA account number (local test code only, no real SMS).')
    p.add_argument('--password', default=None, help='Password for the QA accounts (random when empty).')
    p.add_argument('--keep', action='store_true', help='Keep the QA accounts for manual testing.')
    p.add_argument('--report-dir', default=os.path.join(HERE, 'reports'))
    return QaRun(p.parse_args()).run()


if __name__ == '__main__':
    sys.exit(main())
