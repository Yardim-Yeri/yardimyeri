<?php

namespace App\Jobs;

use App\Services\Sms\Netgsm;
use App\Services\StencilService;
use App\Services\TwitterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\SMS;

class NewHelpNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // parameters for the job from the controller or other source
    public $help;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($help)
    {
        $this->help = $help;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        // //Stencil Service
        // $image_url = StencilService::generateFeaturedImage(strval($this->help->kac_kisilik), $this->help->sehir, $this->help->ihtiyac_turu)["image_url"];
        // // download the image and save it to the public folder

        // $image = file_get_contents($image_url);
        // Log::info($image_url.'-'.$this->help->id);
        // file_put_contents(public_path('images/'.$this->help->id.'.png'), $image);

        // it will save the image to the public folder with the name of the help id

        // Netgsm Service
        $phone_number = $this->help->tel;
        $formatted_number = preg_replace("/[^0-9]/", "", $phone_number);
        Log::alert($formatted_number .'_'. $this->help->id);
        
        $sms =  new Netgsm();
        $sms_content = 'yardimyeri.com\'dan oluşturduğunuz #'. $this->help->id.' numaralı yardım talebiniz başarıyla oluşturulmuştur. Yardım talebiniz onaylandığında size SMS ile bildirilecektir. Geçmiş olsun.';
        $sms->send($formatted_number, $sms_content);
        
        
        SMS::create([
             'sms_content' => $sms_content,
             'case_id' => $this->help->id,
             'recieve_number' => $formatted_number,
             'provider_response' => $sms,
             'data' => json_encode([
             'sms_content' => $sms_content,
             'case_id' => $this->help->id,
             'recieve_number' => $formatted_number,
             'provider_response' => $sms,
             ])
             ]);


        // Twitter Service

        $tweet_template = $this->help->sehir .' ilinde '. $this->help->kac_kisilik. ' kişilik '.$this->help->ihtiyac_turu.' yardımına ihtiyaç var. '.env('APP_URL'). '/yardimda-bulunabilirim'. $this->help->id;
        $tweet = TwitterService::sendTweet($tweet_template);
        Tweet::create([
            'tweet_content' => $tweet_template,
            'case_id' => $this->help->id,
            'status' => $tweet ? 1 : 0,
            'tweet_id' => $tweet->data->id,
            'data' => json_encode($tweet)
        ]);
        // it will send the tweet to the twitter account
    }

}
