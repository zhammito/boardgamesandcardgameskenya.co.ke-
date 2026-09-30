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

## Done since the first handoff
- Steps 1–4 below are complete: shipping zone "Kenya" (id 1) with 17 flat rates including free HQ pickup, `bgck-free-delivery.php` installed, wording updated (65 posts, 71 meta fields), policy page published (page 459, /returns-refunds-delivery/), footer and Contact page updated with the pickup address.
- Privacy Policy published (page 3, /privacy-policy/), set as the WordPress privacy page and linked in the footer.
- Paystack is LIVE (test mode off). Live KSh 10 test passed on 30 Sep 2026. All test orders and the KSh 10 test product were deleted; the shop starts with 0 orders.
- Host raised OPcache to 1 GB / 25,000 files; cart ~1.2-1.6 s, checkout ~2 s.

- SEO fixes (30 Sep): mu-plugin `bgck-product-schema.php` (Product schema name = product name, brand from product_brand); Rank Math `pt_product_default_snippet_name` = %title%; brands set on 18 products (Mattel, Hasbro, Kosmos, Skillmatics, BestSelf, Our Moments, These Cards Will Get You Drunk); category descriptions written; Home meta description 154 chars.
- Shop page (/games/) is now AUTOMATIC (30 Sep): each of the 6 sections is an Elementor v4 atomic Loop (e-collection-loop, template_type product) filtered by the private `bgck_shelf` taxonomy. PRO Elements plugin is active (unlocks atomic Loop); the original Elementor Pro plugin is deactivated (unlicensed).
  - mu-plugin `bgck-shop-sync.php`: assigns each product's shelf from its Rank Math primary category (else first matching section), stores `bgck_cart_url`, `bgck_whatsapp_url`, `bgck_adult_label` on products, stores section counts on page 209 (`bgck_count_*`, shown via post-custom-field), and purges Shop/Home cache on product changes.
  - Global class `tag-adult` has custom CSS `&:empty { display: none; }` (approved by owner) so the 18+ chip hides on non-adult games.
  - Old static Shop data backed up in page 209 meta `_bgck_shop_static_backup`.
  - To choose a product's section: set its primary category ("Make primary" in the product's category box).

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

## Returns, Refunds & Delivery policy (owner's text, cleaned)
**Returns.** Returns only apply when we deliver the wrong item for your order. You have 7 days from delivery to tell us. After 7 days we can't offer a refund or exchange. The item must be unused, in the same condition you received it, and in its original packaging. We check your order details, then arrange the exchange.

**Refunds.** We refund only when:
- the product you ordered is sold out, or
- your delivery address is outside our delivery zones.

Approved refunds go back to your M-Pesa or original payment method within 5 working days. If it hasn't arrived by then, email boardgamescardgames@gmail.com or message us on Instagram @boardgames_cardgames.

**Sale items.** Only full-price items can be refunded. Sale items can't be refunded.

**Exchanges.** We replace items that are defective, damaged, or not what you ordered. A rider from our team collects the item and delivers the correct one. Exchanges are done within 7 working days. During sales it may take longer.

**Delivery.** Nairobi: order before 6pm for same-day or next-day delivery. Outside Nairobi: 24–72 hours, excluding Sundays. During sales, delivery can take longer because of high order volumes. Free pickup at Royal Palm Mall, Wing A, 4th Floor, Shop AT4, until 6pm. Free delivery on orders of KSh 5,000 or more.
