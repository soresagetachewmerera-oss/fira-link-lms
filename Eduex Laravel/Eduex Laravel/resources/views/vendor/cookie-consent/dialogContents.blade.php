<div class="js-cookie-consent cookie-consent" style="
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    background: #101828;
    color: #f8fafc;
    padding: 18px 22px;
    border-radius: 16px;
    box-shadow: 0 25px 45px rgba(15, 23, 42, 0.25);
    display: flex;
    gap: 18px;
    align-items: center;
    max-width: 1000px;
    width: calc(100% - 40px);
    z-index: 9999;
">
    <div class="cookie-consent__message" style="font-size: 15px; line-height: 1.6;">
        {!! trans('cookie-consent::texts.message') !!}
    </div>
    <button class="js-cookie-consent-agree cookie-consent__agree"
        style="
            background: #38bdf8;
            color: #0f172a;
            border: none;
            border-radius: 999px;
            padding: 10px 20px;
            font-weight: 600;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease;
        "
        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 12px 30px rgba(14, 165, 233, 0.35)';"
        onmouseout="this.style.transform=''; this.style.boxShadow='';"
    >
        {{ trans('cookie-consent::texts.agree') }}
    </button>
</div>
