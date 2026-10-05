# Toto Partner Network — interactive concept

A Bengali-first hotel booking and operations prototype for Toto Company, including Hotel Leo International. The single `index.html` contains the application, styling, SVG illustrations and browser-side logic. There is no dependency install or build.

Open the HTML in Chrome, or serve the directory:

```sh
python -m http.server 8000 --bind 127.0.0.1 --directory /workspace/toto-company/demo
```

## Explore the demo

1. Select **agent**, **hotel authority** or **company admin** at the top. The selector changes the demo view; it is not production authentication. Agents/hotel authorities can also select which sample account they represent.
2. Click **add sample bookings** for three explicitly fictional examples, including pending and confirmed requests. Existing bookings are preserved. This button only adds examples once per reset.
3. In agent view, select dates, hotel and room type. Enter guest name/phone, rooms, adults, children and ages, meal plan and special requests. The quote includes room charges, children, meals and sample tax. Accept the displayed policies and submit.
4. The request stays pending and does not reserve inventory. In the matching hotel's view, review it and approve or reject with a reason. Approval rechecks each night of availability and stop-sales.
5. Confirmed bookings support printable/PDF demo vouchers. The browser print dialog saves PDFs. They are not invoices or real reservation documents.
6. An agent may withdraw a pending request or request cancellation of a confirmed booking. The latter continues to hold rooms until the hotel approves cancellation. The hotel can instead keep the booking confirmed. Reasons appear in history.
7. Record a full or partial **demo payment entry** on a confirmed booking. This changes the sample ledger only; it never charges money. The entry cannot exceed the current outstanding amount.
8. Hotel authorities can edit profile/child/cancellation policies, base room counts/rates and a specific date's rate, stock and stop-sale. Existing confirmed inventory cannot be reduced below sold rooms. Previously submitted booking quotes keep their saved prices.
9. Company administrators see all bookings, can add/edit sample agents and their commission rates, and view network reports. Inactive agents cannot submit new requests. Commission is based on room charges; changes apply to new requests.
10. Explore search, status filters, arrival sorting, scoped CSV exports, local activity notifications and reports. CSV cells guard against spreadsheet formula interpretation.

## Sample pricing rules

- Leo hotel photos, address, 14 listed room numbers/types/floors/occupancies and published room prices were retrieved from the owner-selected listing. The nightly prices are converted to INR for this demo. Actual current stock and final terms still require the hotel owner. Other hotels remain fictional samples. Child, meal, tax, check-in/out and cancellation settings are still illustrative, not researched Leo policy.
- Default sample tax: **12%**, applied to room + child + meal charges. This is a demonstration assumption, not tax advice or a determination of an applicable rate.
- Up to the configured free-child age, children share existing beds without child room charges. Older children incur the configured nightly child fee.
- Meal charges apply per adult and per older child per night. A hotel's room type has explicit adult/child occupancy limits.
- Rates can vary by night. The request stores the quote and agent commission rate at submission, preserving that snapshot through later settings changes.
- Payment entries are unverified samples, not receipts. Cancelled bookings can retain entries; the user must reconcile refunds separately. No automatic refund or settlement occurs.

## Data and preservation

New data uses the `toto-network-v3` localStorage key. Prior `toto-demo-v1` records are imported if no new state exists. The old key is preserved; reset only resets the new concept's state. Existing old room counts/rates and booking totals are retained in the import. Stored old quotes can have zero tax/commission because those values did not exist.

Data stays in this browser, on this origin. It is not synchronized between devices, browser profiles or tabs. Use fictional guest information. Reset clears the new demo's changes. Browser storage or clearing data can remove the demo state.

## Verification performed

Real Chromium browser interaction checked pricing, child/meal/tax/commission math, occupancy and consent, pending requests, approval, overlap/overbooking protection, reasons/history, partial demo payment, cancellation request/decline/approval, nightly rate overrides and stop-sales, stock safeguards, preserved quotes, inactive agents, per-role view scope, search/filtering, CSV download, reporting, persistence, migration, sample loading, notifications, voucher/print, escaping of user-entered content, and mobile layout across all roles/pages. No JavaScript errors occurred in those checks. This is prototype validation, not a security or compliance audit.

## Production follow-up

Server-enforced identity/authorization, transactional shared inventory, database and backups, secure personal-data handling, validated hotel content/images, actual tax and occupancy policies, real agent contracts/commission settlement, payment gateway/webhook/refund handling, WhatsApp/SMS/email integrations, channel manager connections, booking modifications, audit retention, full English/other-language localization, actual currency conversion, accessibility review, load testing, deployment and monitoring remain implementation work. Local view switching, notifications and ledger entries do not substitute for those services.

## Screenshots

- `desktop.png`: agent hotel search.
- `admin-dashboard.png`: company overview.
- `owner-dashboard.png`: hotel stock and policies.
- `approval.png`: booking management.
- `booking-form.png`: guest entry and itemized quote.
- `agents.png`: agent network.
- `reports.png`: sample financial reports.
- `voucher.png`: print layout.
- `mobile.png`: mobile hotel search.

Screenshots show illustrative test data. The portable ZIP includes this HTML, guide and screenshots.

## Leo hotel profile and individual rooms

The supplied website is now accessible. Its Hotel → India → Gangtok → Hotel Leo International page lists 14 rooms with numbers, types, floors, occupancy and prices. Six original hotel photographs were downloaded without editing and embedded in the standalone HTML, so the demo does not depend on the listing's live images. Original image files and the research record are also retained in assets/leo and LEO-SOURCES.md.

Toto Company's design, roles, pending/approval workflow and own booking forms remain the product. No external agency name, telephone, booking link or agency branding is displayed in the hotel flow. Research provenance stays in the documentation. The hotel-specific direct phone is not separately published on that page and remains awaiting owner confirmation.

The listing prices are BDT 4,500, 5,000 and 5,500. The indicative currency API for 2026-10-05 gives **1 BDT = 0.78428014 INR**. Converted demo rates, rounded to paise, are **INR 3,529.26**, **3,921.40** and **4,313.54** per room/night. No agency commission or new commercial margin was added to those converted base rates. Extra charges remain the existing configurable demo assumptions. These are not a bank settlement rate or a guarantee of today's hotel selling price.

The 14 room profiles share the representative room-type images used by the source; those images are explicitly not verified photos of the specific numbered room. Room-type inventory is grouped by published category and occupancy. Booking still selects a room type/count, not an assigned physical room number. The hotel must confirm child/extra-bed treatment of occupancy such as 2+1 and 2+2, final inventory, availability, rates and policies.

Prior saved sample booking records/quotes and owner contact entries are preserved. Old sample room types referenced by those records are retained as retired types, excluded from new available inventory. Pending retired-type requests require a new request against the imported room types. Old quote snapshots are not silently converted or repriced.

Hotel authorities can edit contact details, gallery images, individual profiles and inventory. Owner/agent views share those changes within the same browser. Browser entries do not synchronize across devices or publish themselves to GitHub. HTTPS gallery URLs and small JPEG/PNG/WEBP uploads are supported, subject to the local storage quota. Production storage and actual hotel authentication remain separate work.
