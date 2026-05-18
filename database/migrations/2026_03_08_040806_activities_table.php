<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();  // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->string('at_home')->default('no');  // at_home varchar(255) NOT NULL DEFAULT 'no'
            $table->unsignedBigInteger('user_id');  // user_id bigint(20) UNSIGNED NOT NULL
            $table->string('user_type', 250)->nullable();  // user_type varchar(250) DEFAULT NULL
            $table->text('spoken_lang')->nullable();  // spoken_lang text DEFAULT NULL
            $table->integer('duration');  // duration int(11) NOT NULL
            $table->enum('plan_activity', ['no', 'yes'])->default('no');  // plan_activity enum('no','yes') NOT NULL DEFAULT 'no'
            $table->decimal('price', 8, 2);  // price decimal(8,2) NOT NULL
            $table->decimal('decducted_amount', 8, 2)->nullable();  // decducted_amount decimal(8,2) DEFAULT NULL
            $table->enum('type_decducted_amount', ['fixed', 'percent'])->default('percent');  // type_decducted_amount enum('fixed','percent') NOT NULL DEFAULT 'percent'
            $table->bigInteger('capacity')->nullable();  // capacity bigint(20) DEFAULT NULL
            $table->unsignedBigInteger('status_id')->default(1)->comment('1 => Active , 2 => Not Active');  // status_id bigint(20) UNSIGNED NOT NULL DEFAULT '1'
            $table->timestamps();  // created_at and updated_at
            $table->string('city_name_en');  // city_name_en varchar(255) NOT NULL
            $table->string('city_name_ar')->nullable();  // city_name_ar varchar(255) DEFAULT NULL
            $table->string('country_name_ar')->nullable();  // country_name_ar varchar(255) DEFAULT NULL
            $table->string('country_name_en')->nullable();  // country_name_en varchar(255) DEFAULT NULL
            $table->decimal('lat', 9, 6)->nullable();  // lat decimal(9,6) DEFAULT NULL
            $table->decimal('long', 9, 6)->nullable();  // long decimal(9,6) DEFAULT NULL
            $table->boolean('is_tourguideable');  // is_tourguideable tinyint(1) NOT NULL
            $table->decimal('tourguide_price', 8, 2)->nullable();  // tourguide_price decimal(8,2) DEFAULT NULL
            $table->text('description_en');  // description_en text NOT NULL
            $table->text('description_ar')->nullable();  // description_ar text DEFAULT NULL
            $table->string('title_en');  // title_en varchar(255) NOT NULL
            $table->string('title_ar')->nullable();  // title_ar varchar(255) DEFAULT NULL
            $table->unsignedBigInteger('activity_type_id');  // activity_type_id bigint(20) UNSIGNED NOT NULL
            $table->boolean('is_photographer_available')->default(0);  // is_photographer_available tinyint(1) NOT NULL DEFAULT '0'
            $table->decimal('photographer_price', 8, 2)->nullable();  // photographer_price decimal(8,2) DEFAULT NULL
            $table->text('start_date')->nullable();  // start_date text DEFAULT NULL
            $table->string('activity_days')->nullable();  // activity_days varchar(255) DEFAULT NULL
            $table->text('activity_single_dates')->nullable();  // activity_single_dates text DEFAULT NULL
            $table->string('activity_times_start')->nullable();  // activity_times_start varchar(255) DEFAULT NULL
            $table->string('activity_times_end')->nullable();  // activity_times_end varchar(255) DEFAULT NULL
            $table->text('privacy_policy_en');  // privacy_policy_en text NOT NULL
            $table->text('privacy_policy_ar')->nullable();  // privacy_policy_ar text DEFAULT NULL
            $table->text('cancel_policy_en');  // cancel_policy_en text NOT NULL
            $table->text('cancel_policy_ar')->nullable();  // cancel_policy_ar text DEFAULT NULL

            // Add indexes
            $table->index('user_id');
            $table->index('status_id');
            $table->index('activity_type_id');
            $table->index('city_name_en');
            $table->index('country_name_en');

            // Add foreign key constraints (commented out until related tables exist)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('status_id')->references('id')->on('activity_statuses')->onDelete('cascade');
            // $table->foreign('activity_type_id')->references('id')->on('activity_types')->onDelete('cascade');
        });

        // Dummy Data
        // DB::table('activities')->insert([
        //     [
        //         'at_home' => 'no',
        //         'user_id' => 1,
        //         'user_type' => 'host',
        //         'spoken_lang' => 'en,ar',
        //         'duration' => 120,
        //         'plan_activity' => 'yes',
        //         'price' => 50.00,
        //         'decducted_amount' => 10,
        //         'type_decducted_amount' => 'percent',
        //         'capacity' => 10,
        //         'status_id' => 1,
        //         'city_name_en' => 'Cairo',
        //         'city_name_ar' => 'القاهرة',
        //         'country_name_en' => 'Egypt',
        //         'country_name_ar' => 'مصر',
        //         'lat' => 30.044420,
        //         'long' => 31.235712,
        //         'is_tourguideable' => 1,
        //         'tourguide_price' => 20,
        //         'description_en' => 'Explore historic Cairo with a local guide.',
        //         'description_ar' => 'استكشف القاهرة التاريخية مع مرشد محلي.',
        //         'title_en' => 'Cairo Walking Tour',
        //         'title_ar' => 'جولة مشي في القاهرة',
        //         'activity_type_id' => 1,
        //         'is_photographer_available' => 1,
        //         'photographer_price' => 15,
        //         'start_date' => '2026-04-01',
        //         'activity_days' => 'sat,sun',
        //         'activity_single_dates' => null,
        //         'activity_times_start' => '10:00',
        //         'activity_times_end' => '12:00',
        //         'privacy_policy_en' => 'Standard privacy policy.',
        //         'privacy_policy_ar' => 'سياسة الخصوصية القياسية.',
        //         'cancel_policy_en' => 'Cancel before 24 hours.',
        //         'cancel_policy_ar' => 'الإلغاء قبل 24 ساعة.',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'at_home' => 'yes',
        //         'user_id' => 2,
        //         'user_type' => 'host',
        //         'spoken_lang' => 'en',
        //         'duration' => 90,
        //         'plan_activity' => 'no',
        //         'price' => 30,
        //         'decducted_amount' => null,
        //         'type_decducted_amount' => 'percent',
        //         'capacity' => 5,
        //         'status_id' => 1,
        //         'city_name_en' => 'Alexandria',
        //         'city_name_ar' => 'الإسكندرية',
        //         'country_name_en' => 'Egypt',
        //         'country_name_ar' => 'مصر',
        //         'lat' => 31.200092,
        //         'long' => 29.918739,
        //         'is_tourguideable' => 0,
        //         'tourguide_price' => null,
        //         'description_en' => 'Traditional Egyptian cooking experience.',
        //         'description_ar' => 'تجربة طبخ مصري تقليدي.',
        //         'title_en' => 'Egyptian Cooking Class',
        //         'title_ar' => 'دورة الطبخ المصري',
        //         'activity_type_id' => 2,
        //         'is_photographer_available' => 0,
        //         'photographer_price' => null,
        //         'start_date' => '2026-05-10',
        //         'activity_days' => 'fri',
        //         'activity_single_dates' => null,
        //         'activity_times_start' => '15:00',
        //         'activity_times_end' => '16:30',
        //         'privacy_policy_en' => 'Standard privacy policy.',
        //         'privacy_policy_ar' => 'سياسة الخصوصية القياسية.',
        //         'cancel_policy_en' => 'Cancel before 12 hours.',
        //         'cancel_policy_ar' => 'الإلغاء قبل 12 ساعة.',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        // ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
