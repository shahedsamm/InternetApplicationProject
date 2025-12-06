<?php

namespace App\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOtpEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;
    public $otp;

    public function __construct(string $email, string $otp)
    {
        $this->email = $email;
        $this->otp = $otp;
    }

    public function handle()
    {
        Mail::raw("كود التحقق الخاص بك هو: {$this->otp} (صالح لمدة 5 دقائق)", function ($msg) {
            $msg->to($this->email)
                ->subject('رمز التحقق');
        });
    }
}
