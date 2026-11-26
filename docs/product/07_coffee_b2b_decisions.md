# Coffee B2B marketplace — working decisions (2026-11-26)

These answer questions Q1–Q8 of `docs/audits/coffee-b2b-audit-2026-11-25.md`. They come from desk research
(sources below) and common marketplace practice, **not** from ECTA, a lawyer or the business owners.
Each one is configurable or easy to reverse. Change this file when a stakeholder answers differently.

| Status | Meaning |
|---|---|
| **Built** | In the code now |
| **Planned** | Used when that release is built |
| **Open** | Research found no answer; the feature stays off |

## Decisions

| # | Question | Decision | Status | Basis |
|---|---|---|---|---|
| Q1 | Business categories | Category is a label; rights come from capabilities. Alami Laki maps to the ECTA "coffee grower exporter" certificate, Akrabi Laki to "coffee supplier exporter". One business holds one exporter certificate type | **Built** (release 2a) | [1], [2], [3] |
| Q2a | Who may offer coffee to foreign buyers | Only a business with an **approved, unexpired ECTA exporter certificate** (`ecta_exporter_certificate`), on top of Tier 1. Set in `config/verification.php` → `export_offer_documents` | **Built** | Exporters need a certificate of competence and a trade licence [1][2]; suppliers are licensed by regions to supply, not to export [1] |
| Q2b | Lab and taster documents | Not required for grower exporters (Tier 2 rule per category) | Planned (release 2) | Directive 1106/2025 exempts farmer-exporters from the lab requirement [3][4] |
| Q2c | Smallholder farmers | No competence certificate; Tier 1 identity only | Planned (release 3) | Proclamation 1051/2017 exempts smallholders [1] |
| Q2d | Foreign buyers | Own country's company registration; no Ethiopian TIN, licence or bank letter | Planned (release 2) | No Ethiopian rule applies to foreign buyers; Tier 1 today asks for Ethiopian documents |
| Q3 | Web or mobile | App first for all trading. Web only for admin screens and the public seller page, as in CLAUDE.md | **Built** (admin deal screens) | Project rule; launch priority is mobile |
| Q4a | Units | kg base. Feresula = 17 kg, quintal = 100 kg, metric ton = 1,000 kg, lb = 0.45359237 kg. The existing code constant 2.20462262 lb per kg stays | Planned (release 2) | ECX trade data quotes 1 feresula = 17 kg [5] |
| Q4b | The 85 kg note | Not added. No source documents it | **Open** | No source found [6] |
| Q4c | Currency of local trade | ETB. Export prices stay USD | Planned (release 2) | Directive 975/2023 limits domestic sales of export coffee in foreign currency [7] |
| Q4d | Exchange rates | Entered by finance with source and date; no rate means nothing is converted. Never a fixed number in code | Planned (release 2) | Brief Part 3.2 |
| Q5 | Production state | Not researchable. Confirm before any deploy | **Open** | — |
| Q6a | When stock is held | When the buyer **accepts a quote**. Before that, quotes do not hold stock | **Built** | Standard practice: reserve at order capture, expire unconsumed holds [8][9] |
| Q6b | How long | **7 days** to sign the contract (`DEAL_RESERVATION_HOLD_DAYS`). Then the hold is released and an unsigned deal room is cancelled. Once both sign, the stock stays sold | **Built** | Holds need a mandatory expiry, or a buyer can block stock without buying [9] |
| Q6c | Minimum order | A quote must be at least the lot's minimum order. The last bags of a lot can be quoted when fewer than the minimum are left | **Built** | Brief Part 3.2 |
| Q6d | Part deliveries and weight tolerance | Not decided | **Open** (release 2) | — |
| Q7a | Who confirms payments | Fira Link staff (admin) confirm every payment step with a bank reference, on the new admin deal screen. Parties only upload proof | **Built** | Already the rule in `DealRoomPolicy`; brief Part 6 |
| Q7b | Disputes | Admin decides: resume the deal, or cancel it and release the stock. Steps stay frozen until then | **Built** | — |
| Q8 | Local price visibility | Local and wholesale offers visible to verified businesses only | Planned (release 2) | — |
| — | Exporter to exporter sales | The channel stays switched off until ECTA confirms it is allowed | **Open** | No source confirms it [10] |

## Sources

1. Coffee Marketing and Quality Control Proclamation No. 1051/2017 — [FAOLEX text](https://faolex.fao.org/docs/pdf/eth183197.pdf), [ECOLEX summary](https://www.ecolex.org/details/legislation/coffee-marketing-and-quality-control-proclamation-no-10512017-lex-faoc183197/)
2. Rift Valley Institute, *Beyond the Beans: The Political Economy of Coffee in Ethiopia* (2026) — [PDF](https://riftvalley.net/wp-content/uploads/2026/09/RVI-20260929-PEA-Coffee-Synthesis_PRF.pdf)
3. Coffee Marketing and Quality Control Amendment Directive No. 1106/2025 — [Ministry of Justice](https://justice.gov.et/wp-content/uploads/2026/02/1106-The-Coffee-Marketing-and-Quality-Control-amendment-Directive-No.-1106-2025.pdf). The extracted text is garbled; check the gazette copy
4. [Food Business MEA](https://www.foodbusinessmea.com/ethiopia-tightens-coffee-export-rules-with-higher-capital-thresholds-stricter-quality-standards/), [Capital Ethiopia](https://capitalethiopia.com/2025/09/14/ethiopia-drastically-raises-capital-requirements-for-coffee-exporters/), [The Reporter](https://www.thereporterethiopia.com/47018/), on Directive 1106/2025. They disagree on the capital amounts
5. [ECX daily trade data, 24 September 2025 (2merkato)](https://www.2merkato.com/news/capital-market-and-commodity-exchange/8637-ethiopia-commodity-exchange-ecx-daily-trade-data-–-24-september-2025)
6. Searches for an 85 kg coffee sack found only 60 kg, 30 kg and 69–70 kg bags — for example the [Royal Coffee bag weights by origin](https://cdn.royalcoffee.com/wp-content/uploads/2020/03/03092410/Speciality-Coffee-Bag-Weights-by-Origin.pdf)
7. [Dablo Law Firm on Directive 975/2023](https://dablolawfirm.com/legal-update-directive-no-975-2023-for-selling-graded-and-value-added-export-coffee-in-foreign-currency-domestically/)
8. IBM Sterling, [inventory reservations](https://www.ibm.com/docs/en/SSJF7L/dita_files/c_IS_Reservations.html); Adobe Commerce, [order status and reservations](https://experienceleague.adobe.com/en/docs/commerce-admin/inventory/basics/order-status)
9. commercetools, [reserve stock on demand](https://docs.commercetools.com/tutorials/reserve-stock-on-demand) (mandatory reservation expiry against denial-of-stock)
10. [UNIDO coffee value chain analysis, Ethiopia 2025](https://www.unido.org/sites/default/files/unido-publications/2026-05/ETHIOPIA_Value-Chain-Analysis-2025_web.pdf); [ResearchGate, 2020 ECTA sale procedure](https://www.researchgate.net/publication/358022717_Ethiopia_new_coffee_export_procedure_enables_exporters_offer_working_capital_to_suppliers_to_ensure_quality_export)
