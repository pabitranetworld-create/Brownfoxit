# Brown Fox IT – Laravel Website

brownfoxit.com-এর নতুন ওয়েবসাইট, **Laravel 12 (PHP + MySQL)** দিয়ে বানানো।

- **Installer:** যেকোনো cPanel-এ zip আপলোড করে extract করুন, তারপর ডোমেইন খুলুন। একটা Installer নিজে থেকেই খুলবে, সেখানে ডাটাবেস আর Admin-এর তথ্য দিলেই সাইট চালু।
- **Auto-deploy:** GitHub-এর `main` branch-এ নতুন কোড এলে ১-৩ মিনিটের মধ্যে সেটা নিজে থেকেই আপনার হোস্টিং-এ LIVE হয়ে যায়।

---

## ১. cPanel-এ নতুন করে Install (প্রথমবার)

### ধাপ ক: Install zip নামান
GitHub repo → **Actions** → **Build install zip** → **Run workflow** চাপুন। কাজ শেষ হলে (২-৩ মিনিট) ওই run খুলে নিচের **Artifacts** থেকে `brownfoxit-install` নামান। এটার ভেতরে `brownfoxit-install.zip` আছে, যেটায় Laravel-এর সব লাইব্রেরি (`vendor`) দেওয়া থাকে।

> Repo-তে `v1.0.0`-এর মতো একটা tag push করলে zip-টা **Releases** পেজেও চলে আসে।

### ধাপ খ: cPanel-এ আপলোড
1. **cPanel → File Manager** খুলুন। যে ফোল্ডারে সাইট রাখবেন সেখানে `brownfoxit-install.zip` আপলোড করে **Extract** করুন।
   - **ভালো উপায়:** `/home/USER/brownfoxit` ফোল্ডারে রাখুন, তারপর cPanel → **Domains**-এ ডোমেইনের Document Root `/home/USER/brownfoxit/public` করে দিন।
   - **সহজ উপায়:** সরাসরি `public_html`-এ extract করুন। ভেতরের `.htaccess` ফাইলটা নিজে থেকেই সব রিকোয়েস্ট `public` ফোল্ডারে পাঠিয়ে দেবে আর `.env`-এর মতো গোপন ফাইলগুলো লুকিয়ে রাখবে।
2. cPanel → **MySQL® Databases** থেকে:
   - একটা ডাটাবেস বানান (যেমন `cpuser_brownfoxit`)
   - একটা ইউজার বানান
   - **Add User To Database** করে ইউজারকে **ALL PRIVILEGES** দিন
3. cPanel → **Select PHP Version** (বা MultiPHP Manager) থেকে **PHP 8.2 বা 8.3** বেছে নিন।

### ধাপ গ: ব্রাউজারে ডোমেইন খুলুন
Installer নিজে থেকেই খুলে যাবে:

| ধাপ | কী হয় |
|---|---|
| ১. সার্ভার পরীক্ষা | PHP version, extension আর ফোল্ডারের permission ঠিক আছে কি না দেখে। কিছু না থাকলে কী করতে হবে সেটাও বলে দেয়। |
| ২. ডাটাবেস | Host (সাধারণত `localhost`), ডাটাবেসের নাম, ইউজার আর পাসওয়ার্ড দিন। Installer সংযোগ পরীক্ষা করে দেখবে। |
| ৩. ওয়েবসাইট ও Admin | সাইটের নাম, URL আর নতুন Admin-এর নাম, ইমেইল ও পাসওয়ার্ড দিন। চাইলে পুরোনো সাইট থেকে সব ছবি নামানোর অপশন বেছে নিন। |
| ৪. Install | `.env` তৈরি, টেবিল তৈরি, কন্টেন্ট যোগ, Admin তৈরি আর ছবি নামানো, সব একে একে হয়ে যায়। |
| ৫. শেষ | সাইট চালু। **Deploy Token** কপি করে রাখুন, Auto-deploy সেটআপে লাগবে। |

Install শেষ হলে `storage/installed.json` ফাইল তৈরি হয়। এরপর Installer আর কখনো খোলে না। আবার Install করতে চাইলে এই ফাইলটা মুছে দিন।

---

## ২. Auto-deploy সেটআপ (একবারই করতে হয়)

এটা কাজ করে এভাবে: Claude (বা আপনি) GitHub-এ কোড push করে, তখন GitHub Actions নিজে থেকেই `vendor` বানায়। তারপর FTP দিয়ে শুধু বদলানো ফাইলগুলো হোস্টিং-এ আপলোড করে। শেষে সাইটের গোপন `/_deploy` লিংক ডেকে নতুন ডাটাবেস migration চালায় আর cache রিফ্রেশ করে।

1. **cPanel → FTP Accounts** থেকে একটা FTP অ্যাকাউন্ট বানান। এর Directory দিন সাইটের ফোল্ডার, অথবা আপনার মূল FTP অ্যাকাউন্টটাই ব্যবহার করুন।
2. **GitHub repo → Settings → Secrets and variables → Actions → New repository secret** থেকে এগুলো যোগ করুন:

| Secret-এর নাম | উদাহরণ | কোথায় পাবেন |
|---|---|---|
| `FTP_SERVER` | `ftp.brownfoxit.com` | cPanel → FTP Accounts → Configure FTP Client |
| `FTP_USERNAME` | `deploy@brownfoxit.com` | একই জায়গায় |
| `FTP_PASSWORD` | ••••••• | FTP অ্যাকাউন্ট বানানোর সময় যেটা দিয়েছেন |
| `FTP_SERVER_DIR` | `/public_html/` বা `/brownfoxit/` | FTP অ্যাকাউন্টের ভেতরে সাইটের ফোল্ডার। শেষে অবশ্যই `/` দেবেন। |
| `SITE_URL` | `https://www.brownfoxit.com` | আপনার ডোমেইন |
| `DEPLOY_TOKEN` | `353e80a4…` | Installer-এর শেষ পেজ, অথবা সার্ভারের `.env` ফাইল |
| `FTP_PROTOCOL` *(ঐচ্ছিক)* | `ftp` | হোস্টিং-এ FTPS না থাকলে এটা দিন। না দিলে `ftps` ধরা হবে। |

3. ব্যস! এখন থেকে `main` branch-এ যেকোনো push হলেই সাইট আপডেট হয়ে যাবে। প্রতিবারের অবস্থা দেখতে পাবেন GitHub → **Actions** ট্যাবে (সবুজ ✓ মানে LIVE)।

> **প্রথমবারের deploy একটু বেশি সময় নেয়** (১০-২০ মিনিট), কারণ সব ফাইল আপলোড হয়। এর পর থেকে শুধু বদলানো ফাইল যায়, তাই ১-৩ মিনিটেই হয়ে যায়।
>
> **Auto-deploy কখনো এগুলো ছোঁয় না:** সার্ভারের `.env`, `storage/` (লগ, সেশন, Admin থেকে আপলোড করা ফাইল) আর `public/uploads/` (ছবি)।

---

## ৩. নিজের কম্পিউটারে চালানো (ঐচ্ছিক)

**যা লাগবে:** PHP 8.2+, Composer আর MySQL। **Laragon** (Windows) বা **XAMPP** ইনস্টল করলে সব একসাথে পেয়ে যাবেন।

```bash
composer install
php artisan serve
```
এরপর **http://localhost:8000** খুলুন, Installer আসবে।

Terminal দিয়ে সেটআপ করতে চাইলে `.env.example` কপি করে `.env` বানান, সেখানে ডাটাবেসের তথ্য দিন, তারপর `composer run setup` চালান।

---

## ৪. দরকারি কমান্ড

| কমান্ড | কাজ |
|---|---|
| `php artisan site:download-images` | পুরোনো WordPress সাইট থেকে যেসব ছবি নেই সেগুলো নামায় (`--list` দিলে শুধু লিস্ট দেখায়, `--force` দিলে সব আবার নামায়) |
| `php artisan migrate --force` | নতুন ডাটাবেস টেবিল বানায় (Auto-deploy এটা নিজে থেকেই চালায়) |
| `php artisan optimize:clear` | সার্ভারের `.env` হাতে বদলানোর পরে চালাবেন |

---

## ৫. কোন জিনিস কোথায়

| কী | কোথায় |
|---|---|
| পেজের ডিজাইন | `resources/views/` (`layouts/app`, `partials/*`, `home/sections/*`) |
| CSS / JS | `public/assets/css/style.css`, `public/assets/js/main.js` |
| ফন্ট, আইকন, স্লাইডার (সব লোকাল) | `public/vendor/` |
| ছবি | `public/uploads/` |
| Installer | `installer/installer.php` |
| Auto-deploy | `.github/workflows/deploy.yml`, `app/Http/Controllers/DeployController.php` |
| Install zip বানানো | `.github/workflows/release.yml` |
| ডাটাবেস টেবিল / শুরুর কন্টেন্ট | `database/migrations/`, `database/seeders/` |

পরের ধাপের পরিকল্পনা আর কোড লেখার নিয়ম আছে `PROJECT_NOTES.md`-তে।
