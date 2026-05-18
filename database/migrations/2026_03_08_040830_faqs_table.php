<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id(); // id int(11) NOT NULL AUTO_INCREMENT
            $table->string('name_en'); // name_en varchar(255) NOT NULL
            $table->string('name_ar'); // name_ar varchar(255) NOT NULL
            $table->text('describtion_en'); // describtion_en text NOT NULL
            $table->text('describtion_ar'); // describtion_ar text NOT NULL
            $table->timestamps(); // created_at and updated_at
        });

        // Insert the FAQs from your SQL dump
        DB::table('faqs')->insert([
            [
                'id' => 1,
                'name_en' => 'Cancellation Policyvvv test from web',
                'name_ar' => 'سياسة الإلغاء3',
                'describtion_en' => 'Cancellation Policy\r\nIt is simply placeholder text (meaning the purpose is the form rather than the content) used in the printing and publishing industries. Lorem Ipsum has been and remains the standard for placeholder text since the 15th century when an unknown printer took a galley of type and scrambled it to make a type specimen book. test from web',
                'describtion_ar' => 'سياسة الإلغاء\r\nهو ببساطة نص بديل (أي أن الغرض هو الشكل لا المحتوى) يُستخدم في صناعات الطباعة والنشر. ظلّ نص لوريم إيبسوم، ولا يزال، معيارًا للنص البديل منذ القرن الخامس عشر، عندما قام طابع مجهول بخلط ورق الطباعة لإنشاء كتاب نماذج الطباعة. test from web\r\nالشرط 2\r\nهو ببساطة نص بديل (أي أن الغرض هو الشكل لا المحتوى) يُستخدم في صناعات الطباعة والنشر. ظلّ نص لوريم إيبسوم، ولا يزال، معيارًا للنص البديل منذ القرن الخامس عشر، عندما قام طابع مجهول بخلط ورق الطباعة لإنشاء كتاب نماذج الطباعة.',
                'created_at' => '2025-08-20 13:37:13',
                'updated_at' => '2025-08-20 19:37:13'
            ],
            [
                'id' => 2,
                'name_en' => 'Terms and Conditions',
                'name_ar' => 'سياسة الخصوصية',
                'describtion_en' => 'Terms and Conditions\nIt is simply placeholder text (meaning the purpose is the form rather than the content) used in the printing and publishing industries. Lorem Ipsum has been and remains the standard for placeholder text since the 15th century when an unknown printer took a galley of type and scrambled it to make a type specimen book.',
                'describtion_ar' => 'الشروط و الأحكام\nهو ببساطة نص بديل (أي أن الغرض هو الشكل لا المحتوى) يُستخدم في صناعات الطباعة والنشر. ظلّ نص لوريم إيبسوم، ولا يزال، معيارًا للنص البديل منذ القرن الخامس عشر، عندما قام طابع مجهول بخلط ورق الطباعة لإنشاء كتاب نماذج الطباعة.\nالشرط 2\nهو ببساطة نص بديل (أي أن الغرض هو الشكل لا المحتوى) يُستخدم في صناعات الطباعة والنشر. ظلّ نص لوريم إيبسوم، ولا يزال، معيارًا للنص البديل منذ القرن الخامس عشر، عندما قام طابع مجهول بخلط ورق الطباعة لإنشاء كتاب نماذج الطباعة.',
                'created_at' => '2025-08-06 15:24:17',
                'updated_at' => '2025-08-06 21:24:17'
            ],
            [
                'id' => 3,
                'name_en' => 'Refunds and Compensation',
                'name_ar' => 'المبالغ المستردة والتعويضات',
                'describtion_en' => 'Refunds and Compensation\nIt is simply placeholder text (meaning the purpose is the form rather than the content) used in the printing and publishing industries. Lorem Ipsum has been and remains the standard for placeholder text since the 15th century when an unknown printer took a galley of type and scrambled it to make a type specimen book.',
                'describtion_ar' => 'المبالغ المستردة والتعويضات\nهو ببساطة نص بديل (أي أن الغرض هو الشكل لا المحتوى) يُستخدم في صناعات الطباعة والنشر. ظلّ نص لوريم إيبسوم، ولا يزال، معيارًا للنص البديل منذ القرن الخامس عشر، عندما قام طابع مجهول بخلط ورق الطباعة لإنشاء كتاب نماذج الطباعة.\nالشرط 2\nهو ببساطة نص بديل (أي أن الغرض هو الشكل لا المحتوى) يُستخدم في صناعات الطباعة والنشر. ظلّ نص لوريم إيبسوم، ولا يزال، معيارًا للنص البديل منذ القرن الخامس عشر، عندما قام طابع مجهول بخلط ورق الطباعة لإنشاء كتاب نماذج الطباعة.',
                'created_at' => '2025-05-28 03:42:55',
                'updated_at' => '2025-05-28 03:42:55'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
