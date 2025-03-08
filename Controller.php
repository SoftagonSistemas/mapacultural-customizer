<?php

namespace OneClick;

use DateTime;
use Exception;
use Throwable;
use TypeError;
use RuntimeException;
use \MapasCulturais\App;
use InvalidArgumentException;
use MapasCulturais\Exceptions\NotFound;
use MapasCulturais\Traits\ControllerAPI;
use MapasCulturais\Exceptions\MailTemplateNotFound;
use MapasCulturais\Exceptions\PermissionDenied;
use MapasCulturais\Exceptions\WorkflowRequest;
use MapasCulturais\i;

class Controller  extends \MapasCulturais\Controllers\EntityController
{
    use ControllerAPI;

    function __construct()
    {
        parent::__construct();
        $this->entityClassName = '\OneClick\Settings';
    }

    /**
     * @return void 
     * @throws RuntimeException 
     * @throws InvalidArgumentException 
     * @throws NotFound 
     * @throws Exception 
     */
    public function GET_steps(): void
    {
        $app = App::i();

        $this->requireAuthentication();

        if (!$app->user->is('admin')) {
            $app->pass();
        }

        $this->render('settings', []);
    }


    /**
     * @return void 
     * @throws RuntimeException 
     * @throws InvalidArgumentException 
     * @throws NotFound 
     * @throws Exception 
     * @throws MailTemplateNotFound 
     * @throws TypeError 
     * @throws Throwable 
     */
    public function POST_sendMailTest(): void
    {
        $app = App::i();

        $this->requireAuthentication();

        if (!$app->user->is('admin')) {
            $app->pass();
        }

        $email = $this->data['email'];
        $params = [
            'siteName' => $app->siteName
        ];

        $message = $app->renderMailerTemplate('email_teste_settings', $params);
        $email_params = [
            'from' => $app->config['mailer.from'],
            'to' => $email,
            'subject' => $message['title'],
            'body' => $message['body'],
        ];

        $send = $app->createAndSendMailMessage($email_params);
        $this->json($send);
    }

    /**
     * @return void 
     * @throws RuntimeException 
     * @throws InvalidArgumentException 
     * @throws NotFound 
     * @throws PermissionDenied 
     * @throws WorkflowRequest 
     */
    public function POST_upload()
    {
        $app = App::i();

        $this->requireAuthentication();

        if (!$app->user->is('admin')) {
            $app->pass();
        }

        if (isset($_FILES['ocFileUpload']) && $_FILES['ocFileUpload']['error'] === UPLOAD_ERR_OK) {
            $oldName = basename($_FILES['ocFileUpload']['name']);
            $fileTmpPath = $_FILES['ocFileUpload']['tmp_name'];
            $new_name = (new DateTime("now"))->getTimestamp();
            $ext = pathinfo($oldName, PATHINFO_EXTENSION);
            $prop = $this->data['prop'];

            if (isset($this->data['imageFinalName'])) {
                $new_name = $this->data['imageFinalName'];
            }

            $dir = __DIR__ . "/files";
            if (isset($this->data['dir'])) {
                $dir = __DIR__ . "/" . $this->data['dir'];
            }

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                chown($dir, 'www-data');
                chgrp($dir, 'www-data');
            }

            $path = $dir . "/" . $new_name . ".$ext";
            if (file_exists($path)) {
                unlink($path);
            }

            /** @var Settings $settings */
            if ($settings = $app->repo('OneClick\\Settings')->find($this->data['id'])) {

                $metadataFiles = $settings->fromToFilesMetadata();
                $metadata = $metadataFiles[$prop];

                $bannerImageData = [];
                $old_image = null;
                if ($bannerImageData = $settings->$metadata) {
                    $old_image = $bannerImageData->path;
                }

                $bannerImageData = [
                    'prop' => $prop,
                    'path' => $path,
                    'settingsId' => $settings->id,
                    'oldName' => $oldName,
                    'ext' => $ext,
                    'dateUpload' => (new DateTime("now"))->format('Y-m-d H:i:s'),
                    'new_name' => $new_name . ".$ext",
                ];

                if (move_uploaded_file($fileTmpPath, $path)) {
                    chown($path, 'www-data');
                    chgrp($path, 'www-data');

                    if ($old_image && file_exists($old_image)) {
                        unlink($old_image);
                    }

                    $settings->$metadata = $bannerImageData;
                    $settings->save(true);
                    $this->json($bannerImageData);
                }
            }
        }

        $this->json(false);
    }


    public function  POST_clearCache()
    {
        $app = App::i();

        $this->requireAuthentication();

        if (!$app->user->is('admin')) {
            $app->pass();
        }

        $cache_id = "ocCostumizerColors";
        if ($app->mscache->contains($cache_id)) {
            $app->mscache->delete($cache_id);
        }

        $this->json(true);
    }

    function ALL_clearCache()
    {
        $app = App::i();
        $url = $app->createUrl('settings', 'steps');
        if ($app->user->is('superAdmin')) {
            $app->cache->flushAll();
        }

        if ($app->user->is('saasSuperAdmin')) {
            $app->mscache->flushAll();
        }
        header("Location: {$url}");
        exit;
    }
}
