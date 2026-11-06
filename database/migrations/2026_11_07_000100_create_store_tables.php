<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E-commerce: products, categories, carts, orders, payments, downloads, coupons.
 * Money is stored as DECIMAL(12,2) in the store currency.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            return;
        }

        Schema::create('product_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->string('image')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('type', 20)->default('digital');            // digital, physical
            $t->string('status', 20)->default('draft')->index();    // draft, published, archived
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('sku', 100)->nullable();
            $t->foreignId('product_category_id')->nullable()->constrained()->nullOnDelete();
            $t->string('tags')->nullable();
            $t->text('short_description')->nullable();
            $t->longText('description')->nullable();
            $t->string('image')->nullable();
            $t->json('gallery')->nullable();
            $t->string('video_url')->nullable();
            $t->decimal('price', 12, 2)->default(0);
            $t->decimal('sale_price', 12, 2)->nullable();
            $t->timestamp('sale_starts_at')->nullable();
            $t->timestamp('sale_ends_at')->nullable();
            $t->boolean('manage_stock')->default(false);
            $t->integer('stock')->nullable();
            $t->decimal('weight', 10, 3)->nullable();
            $t->json('variants')->nullable();        // [{key, name, sku, price, sale_price, stock}]
            $t->json('files')->nullable();           // [{key, name, path, version, variant}]
            $t->json('features')->nullable();        // [{text}]
            $t->json('specifications')->nullable();  // [{label, value}]
            $t->text('requirements')->nullable();
            $t->text('compatibility')->nullable();
            $t->json('faqs')->nullable();            // [{q, a}]
            $t->string('documentation_url')->nullable();
            $t->string('demo_url')->nullable();
            $t->string('version', 50)->nullable();
            $t->longText('changelog')->nullable();
            $t->unsignedInteger('download_limit')->nullable();
            $t->unsignedInteger('download_expiry_days')->nullable();
            $t->boolean('is_featured')->default(false);
            $t->json('related_ids')->nullable();
            $t->string('seo_title')->nullable();
            $t->string('seo_description', 500)->nullable();
            $t->string('og_image')->nullable();
            $t->string('source_url')->nullable();    // imported from (WordPress)
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });

        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('type', 10)->default('percent'); // percent, fixed
            $t->decimal('value', 12, 2);
            $t->decimal('min_subtotal', 12, 2)->nullable();
            $t->unsignedInteger('max_uses')->nullable();
            $t->unsignedInteger('used_count')->default(0);
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->string('description')->nullable();
            $t->timestamps();
        });

        Schema::create('carts', function (Blueprint $t) {
            $t->id();
            $t->string('token', 64)->unique();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('coupon_code', 50)->nullable();
            $t->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('variant_key', 40)->nullable();
            $t->unsignedInteger('qty')->default(1);
            $t->timestamps();
            $t->unique(['cart_id', 'product_id', 'variant_key']);
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('number', 40)->unique();
            $t->string('access_key', 64);                        // lets guests view their own order
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('status', 20)->default('pending')->index();          // pending, on_hold, processing, completed, cancelled, refunded, failed
            $t->string('payment_status', 20)->default('unpaid')->index();   // unpaid, pending, paid, refunded, failed
            $t->string('payment_method', 40)->nullable();
            $t->string('currency', 10);
            $t->decimal('subtotal', 12, 2);
            $t->decimal('discount', 12, 2)->default(0);
            $t->decimal('shipping', 12, 2)->default(0);
            $t->decimal('tax', 12, 2)->default(0);
            $t->decimal('total', 12, 2);
            $t->string('coupon_code', 50)->nullable();
            $t->json('billing');
            $t->json('shipping_address')->nullable();
            $t->boolean('needs_shipping')->default(false);
            $t->text('customer_note')->nullable();
            $t->text('admin_note')->nullable();
            $t->string('manual_reference')->nullable();          // e.g. bKash transaction ID typed by the customer
            $t->string('ip', 45)->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });

        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $t->string('variant_key', 40)->nullable();
            $t->string('name');
            $t->string('variant_name')->nullable();
            $t->string('sku', 100)->nullable();
            $t->string('type', 20)->default('digital');
            $t->decimal('price', 12, 2);
            $t->unsignedInteger('qty');
            $t->decimal('total', 12, 2);
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('gateway', 40);
            $t->string('reference', 64)->unique();               // our transaction id sent to the gateway
            $t->string('gateway_txn_id', 120)->nullable();
            $t->decimal('amount', 12, 2);
            $t->string('currency', 10);
            $t->string('status', 20)->default('initiated')->index(); // initiated, pending, success, failed, cancelled
            $t->string('method', 60)->nullable();                // bKash, card …
            $t->text('message')->nullable();
            $t->json('meta')->nullable();                        // gateway responses (no secrets, no card data)
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
            $t->unique(['gateway', 'gateway_txn_id']);
        });

        Schema::create('download_permissions', function (Blueprint $t) {
            $t->id();
            $t->string('token', 64)->unique();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('order_item_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $t->string('file_key', 40);
            $t->string('file_name');
            $t->unsignedInteger('downloads_remaining')->nullable();
            $t->unsignedInteger('download_count')->default(0);
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->timestamp('last_download_at')->nullable();
            $t->timestamps();
        });

        Schema::create('order_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type', 30);       // created, status, payment, note, email, download
            $t->text('message');
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->string('phone', 60)->nullable()->after('email');
            $t->json('address')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        foreach (['order_events', 'download_permissions', 'payments', 'order_items', 'orders', 'cart_items', 'carts', 'coupons', 'products', 'product_categories'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['phone', 'address']));
    }
};
