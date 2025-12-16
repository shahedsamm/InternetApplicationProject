<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Jobs\SendAccountBlockedEmailJob;

class SendAccountBlockedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $email
    ) {}

    public function handle(): void
    {
        Mail::raw(
            "تم قفل حسابك لمدة 10 دقائق بسبب إدخال كود تحقق خاطئ 3 مرات.\n\nيرجى المحاولة لاحقًا.",
            function ($message) {
                $message->to($this->email)
                        ->subject('⚠️ تم قفل حسابك مؤقتًا');
            }
        );
    }
}
