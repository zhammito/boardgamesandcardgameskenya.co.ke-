# Handoff: boardgamesandcardgameskenya.co.ke

Everything below is still to do. The site is built, published, and fast. Emails and Paystack (test mode) work.

## How to work on the site
- Use the Novamira connector ability `novamira/execute-php` (param `code`).
- The hosting firewall (Imunify) blocks some requests. Avoid literal `https://` (write `'ht'.'tps:'`), `base64_decode`, `gzinflate`, `shell_exec`/`passthru`, `information_schema`, and the words Authorization/Bearer. For file uploads use `$z='gzun'.'compress'; $z(hex2bin('...'))` (zlib-compressed hex).
- If a call returns "Invalid content from server", wait about 3 minutes, then retry with a smaller call. Keep each call under 45 s.
- Do not touch the other 7 sites on the hosting account (public_html, smartpoint, mariganretailers, luminashoppe, accessoryshop, suegadgethub, www).

## Already done this session
- mu-plugins installed: `bgck-cache-warmer.php` (every 10 min via cron) and `bgck-order-alert-delivery.php` (strips Reply-To from shop alerts so Gmail delivers them). Copies are in this folder.
- `DISABLE_WP_CRON` set in wp-config.php. A cPanel cron runs wp-cron.php every 5 min.
- Shop email is boardgamescardgames@gmail.com everywhere. WP Mail SMTP uses Other SMTP (admin@ mailbox, SSL 465).
- Paystack: test mode, working (test orders #456 and #457). Live keys not yet entered.

## To do next
1. **Delivery zones.** Create one WooCommerce shipping zone "Kenya" (country KE). Add:
   - a flat rate for each line in `delivery-rates.json` (title, cost in KSh)
   - a free flat rate (cost 0) titled "HQ Pick Up: Royal Palm Mall, Wing A, 4th Floor, Shop AT4 (FREE, pick up until 6pm)", placed first. The owner confirmed this is their pickup point.
   - Make sure `woocommerce_calc_shipping` is `yes`.
2. **Free delivery over KSh 5,000.** Upload `bgck-free-delivery.php` to `wp-content/mu-plugins/`.
3. **Delivery wording** (Elementor `_elementor_data` and post content, pages 115, 209, 211, 213, 215, header 262, footer 263, and product descriptions):
   - `[CUT-OFF TIME]` → `6pm`
   - Shop banner: "Order before 6pm for same-day or next-day delivery in Nairobi, or have it sent anywhere in Kenya."
   - "Same-day delivery in Nairobi" → "Same-day or next-day delivery in Nairobi"
   - After editing, clear `_elementor_element_cache` and run `do_action('litespeed_purge_all')`.
4. **Returns, Refunds & Delivery page.** Build it with Elementor v4 atomic elements and the existing global classes: no HTML widget, no shortcode, no CSS. Link it in the footer. Content is below.
5. **Go live with Paystack.** The owner enters the live keys and unticks Test Mode, then does one real KSh 10 test purchase (make a hidden product). After that, delete test orders #456, #457 and draft #439.
6. **SEO Part B.** Google Search Console verification code and sitemap submission.

## Ready-to-run scripts (steps 1–5)
Steps 1–5 are written as scripts in `handoff/scripts/`, ready for `novamira/execute-php`. Each one avoids the words the firewall blocks and stays well under 45 s. They are not run yet: in the session that wrote them, the Novamira connector failed to connect (429) and the network policy blocked the site. Paste each file's contents as `code`. If the ability won't accept the opening `<?php` line, remove it.

| Order | Script | What it does | Dry run first? |
|---|---|---|---|
| 1 | `01-delivery-zones.php` | Kenya zone (KE), HQ pickup free and first, then the 16 rates from `delivery-rates.json`, tax off, `woocommerce_calc_shipping = yes`. Updates existing methods with the same title rather than duplicating them. | Optional (`$apply = false`) |
| 2 | `02-free-delivery-plugin.php` | Writes `mu-plugins/bgck-free-delivery.php`. It prints the md5, which should be `8968bb77104ddd631adccf076a39ddad`. | No |
| 3 | `03-delivery-wording.php` | Replaces `[CUT-OFF TIME]` with 6pm, "same-day delivery" with "same-day or next-day delivery", and "arrive the same day" with "…or the next day". It sets the "Order before…" shop banner to the new line. Pages 115/209/211/213/215, header 262, footer 263, all products, plus any other post that contains these phrases. It then clears `_elementor_element_cache` and the Elementor CSS and purges LiteSpeed. | **Yes.** Read the list, then set `$apply = true` |
| 4 | `04-returns-page.php` | Builds `/returns-refunds-delivery/` from e-flexbox/e-div-block, e-heading and e-paragraph, using the global classes page-banner, container, page-head, h1, lead, section, faq-list, faq-item, h3 and body-text. No local styles, no HTML widget, no shortcode. It copies the prop shapes from existing pages so they match this Elementor version. It then adds a footer link by copying the "Delivery and pickup" link. | **Yes.** Check the class labels it found and the footer-link copy, then apply. Open the page in the Elementor editor once afterwards. |
| 5a | `05a-paystack-check.php` | Read-only. Shows the mode, whether each key is filled in (masked), the currency and the webhook URL. | Read-only |
| 5b | `05b-paystack-test-product.php` | Creates a hidden, virtual KSh 10 product (SKU BGCK-LIVE-TEST) and prints its add-to-cart link. | No |
| 5c | `05c-paystack-cleanup.php` | Deletes orders #456, #457 and draft #439 and the test product. Keeps the real KSh 10 order. | **Yes** |

`bgck-free-delivery.php` label changed from "over KSh 5,000" to "of KSh 5,000 or more" to match the code (`>= 5000`) and the policy text.

## Paystack go-live checklist (owner)
1. **Activate the Paystack business.** In the Paystack dashboard, complete Compliance (business details, KRA PIN, ID and a payout M-Pesa or bank account) until the Live toggle unlocks. Nothing below works until this is done.
2. In Paystack, open **Settings → API Keys & Webhooks** and switch to **Live**. Copy the live public key (`pk_live_…`) and live secret key (`sk_live_…`). Paste the webhook URL that `05a` prints (it ends `?wc-api=Tbz_WC_Paystack_Webhook`) into **Live Webhook URL** and save.
3. In WordPress, open **WooCommerce → Settings → Payments → Paystack**. Paste both live keys, **untick Test Mode** and save. Never send the secret key in chat or email.
4. Run `05a` again. It should show Test mode `no` and both live keys starting `pk_live_` / `sk_live_`.
5. Run `05b`. In a private window, open the add-to-cart link and check out with a real M-Pesa number, KSh 10.
6. Check that the order goes to Processing, the shop alert email arrives at boardgamescardgames@gmail.com, and the payment appears under Transactions in the Paystack Live dashboard.
7. Refund the KSh 10 from the Paystack dashboard. Then run `05c` with `$apply = true`.
8. If the order stays Pending, the webhook isn't reaching the site, usually because Imunify blocks it. Whitelist Paystack's webhook IPs (52.31.139.75, 52.49.173.169, 52.214.14.220) in Imunify or ask the host to.

## Returns, Refunds & Delivery policy (owner's text, cleaned)
**Returns.** Returns only apply when we deliver the wrong item for your order. You have 7 days from delivery to tell us. After 7 days we can't offer a refund or exchange. The item must be unused, in the same condition you received it, and in its original packaging. We check your order details, then arrange the exchange.

**Refunds.** We refund only when:
- the product you ordered is sold out, or
- your delivery address is outside our delivery zones.

Approved refunds go back to your M-Pesa or original payment method within 5 working days. If it hasn't arrived by then, email boardgamescardgames@gmail.com or message us on Instagram @boardgames_cardgames.

**Sale items.** Only full-price items can be refunded. Sale items can't be refunded.

**Exchanges.** We replace items that are defective, damaged, or not what you ordered. A rider from our team collects the item and delivers the correct one. Exchanges are done within 7 working days. During sales it may take longer.

**Delivery.** Nairobi: order before 6pm for same-day or next-day delivery. Outside Nairobi: 24–72 hours, excluding Sundays. During sales, delivery can take longer because of high order volumes. Free pickup at Royal Palm Mall, Wing A, 4th Floor, Shop AT4, until 6pm. Free delivery on orders of KSh 5,000 or more.
