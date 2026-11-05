<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moduł Serwis: naprawy przedmiotów. Otwarta naprawa = returned_at puste.
 * contact_id to serwis z modułu Kontakty, service_name to nazwa zapisana w
 * chwili wysyłki (albo wpisana ręcznie; puste = naprawa we własnym zakresie),
 * tak samo jak loans.borrower_name. previous_status: status przedmiotu przed
 * wysyłką, przywracany po zakończeniu naprawy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service_name')->nullable();
            $table->text('fault_description');
            $table->date('sent_at');
            $table->date('expected_at')->nullable();
            $table->date('returned_at')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->text('work_done')->nullable();
            $table->string('previous_status', 20)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};
