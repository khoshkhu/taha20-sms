<?php

namespace Taha20\Sms;

use Illuminate\Database\Eloquent\Model;

class Sms extends Model
{
    //
    protected $table = 'sms';
    protected $fillable = ['messageId','mobile','text','method','senderNumber','flash','status','send_at','type'];
}
