# UpUp - Refactor Scaffold

این شاخه شامل اسکلت اولیهٔ معماری پیشنهادی برای بازسازی ربات آپلودر است.

هدف: ایجاد ساختار ماژولار، خدمات مستقل (DB, Telegram, Crypto)، نصب‌کنندهٔ ابتدایی و مهاجرت SQL.

فایل‌های مهم:
- .env.example — نمونه پیکربندی
- bot/core — کلاس‌های پایه
- bot/services — Database, TelegramClient, Crypto
- bot/migrations/001_init.sql — ساختار دیتابیس جدید
- public/webhook.php — endpoint وبهوک
- public/installer.php — نصب‌کنندهٔ پایه

تذکر: توکن‌ها و کلیدها در کد هاردکد نشده‌اند. پس از نصب فایل .env تولید خواهد شد و کلید محرمانه در storage/keys قرار می‌گیرد.
