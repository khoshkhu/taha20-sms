<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('messageId')->nullable();
            $table->string('mobile',15);
            $table->text('text');
            $table->enum('method',array('url','web-service'))->default('web-service');
            $table->string('senderNumber',40);
            $table->tinyInteger('flash')->default(1);
            $table->tinyInteger('status')->default(10);
            $table->timestamp('send_at');
            $table->enum('type',array('send','receipt'))->default('send');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sms');
    }
}
