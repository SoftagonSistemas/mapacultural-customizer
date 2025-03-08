<?php

namespace OneClick;

use DateTime;
use Exception;
use MapasCulturais\i;
use OneClick\Settings;
use MapasCulturais\App;

class Plugin extends \MapasCulturais\Plugin
{
    public function __construct($config = [])
    {
        $config += [];

        parent::__construct($config);
    }

    /**
     * @return void 
     */
    public function _init(): void
    {
        $app = App::i();

        $self = $this;

        $app->view->enqueueStyle('app-v2', 'OneClick-v2', 'css/plugin-OneClick.css');

        $driver = $app->em->getConfiguration()->getMetadataDriverImpl();
        $driver->addPaths([__DIR__]);

        // Insere a entidade no EntitiesDescription
        $app->hook('mapas.printJsObject:before', function () {
            $this->jsObject['EntitiesDescription']['settings'] = Settings::getPropertiesMetadata();
        });

        // Define a entidade na lista de ENUM
        $app->hook('doctrine.emum(object_type).values', function (&$values) {
            $values['Settings'] = Settings::class;
        });

        // Personalização de ícones
        $app->hook('component(mc-icon).iconset', function (&$iconset) {
            $iconset['one-click-brush'] = "la:brush";
            $iconset['one-click-settings'] = "ic:outline-settings";
            $iconset['one-click-text-outline'] = "mdi:card-text-outline";
            $iconset['one-click-image'] = "majesticons:image";
            $iconset['one-click-colors-sharp'] = "material-symbols:colors-sharp";
            $iconset['one-click-dialog'] = 'wpf:ask-question';
            $iconset['one-click-close-rounded'] = 'material-symbols:close-rounded';

            $iconset['one-click-facebook'] = 'mdi:facebook';
            $iconset['one-click-instagram'] = 'mdi:instagram';
            $iconset['one-click-linkedin'] = 'mdi:linkedin';
            $iconset['one-click-pinterest'] = 'mdi:pinterest';
            $iconset['one-click-spotify'] = 'mdi:spotify';
            $iconset['one-click-tiktok'] = 'mdi:tiktok';
            $iconset['one-click-x'] = 'mdi:twitter';
            $iconset['one-click-vimeo'] = 'mdi:vimeo';
            $iconset['one-click-youtube'] = 'mdi:youtube';
            $iconset['one-click-upload'] = 'et:upload';
            $iconset['one-click-edit'] = 'tabler:edit';
        });

        // Garante o registro de metadados em todas as requisições
        $app->hook('<<*>>(<<*>>.<<*>>):before', function () use ($self) {
            $self->oneClickRegisteredMetadata();
        });

        // hook responsável por setar as configurações em seus devidos lugares
        $app->hook('app.register:after', function () use ($self, $app) {
            if (php_sapi_name() != "cli") {
                $app->disableAccessControl();

                $settings = $self->getSettings();

                if ($settings) {
                    $self->setEmailSettings($settings, $app);
                    $self->setRecaptchaSettings($settings, $app);
                    $self->setGeoSettings($settings, $app);
                    $self->setSocialMedia($settings, $app);
                    $self->setImagesHome($settings, $app);
                    $self->setTextsHome($settings, $app);
                    $self->setLogoDefinitions($settings, $app);
                    $self->setFaviconDefinitions($settings, $app);
                    $self->setShare($settings, $app);
                    $self->setMailImage($settings, $app);
                    $self->setColors($settings, $app);
                    $app->view->jsObject['fromToFilesMetadata'] = $settings->fromToFilesMetadata();
                }

                $app->enableAccessControl();
            }
        });

        // Insere novo menu no painel do usuario
        $app->hook('panel.nav',function(&$nav_items) use($app) {
            $nav_items['oneclick'] = [
                'label' => i::__('OneClick'),
                'condition' => function () use ($app) {
                    return $app->user->is('admin');
                },
                'items' => [
                    ['route' => 'settings/steps', 'icon' => 'one-click-brush', 'label' => i::__('Editor')],
                ]
            ];
        });
    }

    /**
     * @return void 
     * @throws Exception 
     */
    public function register(): void
    {
        $app = App::i();

        $app->registerController('settings', Controller::class);

        $this->oneClickRegisteredMetadata();
    }

    /**
     * @return void 
     */
    public function oneClickRegisteredMetadata(): void
    {
        $app = App::i();
        include __DIR__ . "/registereds/metadata.php";
        foreach ($metadata as $key => $cfg) {
            $this->registerMetadata('OneClick\\Settings', $key, $cfg);
        }
    }

    /**
     * @param null|Settings $settings 
     * @param App $app 
     * @return void 
     */
    public function setEmailSettings(?Settings $settings, App $app): void
    {
        $app->config['mailer.templates']['email_teste_settings'] = [
            'title' => i::__("{$app->siteName} - Teste de configuração de email"),
            'template' => 'email_teste_settings.html'
        ];

        $mailer_trasport = "smtp://";

        if ($settings->mailer_user) {
            $mailer_trasport .= $settings->mailer_user;
        }

        if ($settings->mailer_password) {
            $mailer_trasport .= ":{$settings->mailer_password}";
        }

        if ($settings->mailer_host) {
            $mailer_trasport .= "@{$settings->mailer_host}";
        }

        if ($settings->mailer_protocol && $settings->mailer_protocol !== "LOCAL") {
            $mailer_trasport .= $settings->mailer_protocol === 'SSL' ? ':465' : ':587';
        } else {
            $mailer_trasport .= ":1025";
        }

        $app->config['mailer.transport'] = $mailer_trasport;
        $app->config['mailer.from'] = $settings->mailer_email ? $settings->mailer_email : "sysadmin@localhost";
    }

    /**
     * @param null|Settings $settings 
     * @param App $app 
     * @return void 
     */
    public function setRecaptchaSettings(?Settings $settings, App $app): void
    {
        $auth = [];
        if ($settings->recaptcha_secret) {
            $auth['google-recaptcha-secret'] = $settings->recaptcha_secret;
        }

        if ($settings->recaptcha_sitekey) {
            $auth['google-recaptcha-sitekey'] = $settings->recaptcha_sitekey;
        }

        if ($settings->recaptcha_sitekey && $settings->recaptcha_secret) {
            file_put_contents(__DIR__ . "/files/auth.txt", json_encode($auth));
        }
    }

    /**
     * @param null|Settings $settings 
     * @param App $app 
     * @return void 
     */
    public function setGeoSettings(?Settings $settings, App $app): void
    {
        if ($values = $settings->geoDivisionsFilters) {
            $geoDivisionsFilters = [];
            $fromTo = $this->getFromToGeoFilters();
            foreach ($values as $value) {
                $geoDivisionsFilters[] = $fromTo[$value];
            }

            $app->config['app.geoDivisionsFilters'] = $geoDivisionsFilters;
        }

        if ($values = $settings->geodivisions) {
            $geoDivisionsHierarchy = $this->getFromToGeoDivisionsHierarchy();
            foreach ($values as $value) {
                $name = $geoDivisionsHierarchy[$value];
                $app->config['app.geoDivisionsFilters'][$value] = ['name' => $name, 'showLayer' => true];
            }
        }

        if ($settings->zoom_default) {
            $app->config['maps.zoom.default'] = $settings->zoom_default;
        }

        if ($settings->zoom_max) {
            $app->config['maps.zoom.max'] = $settings->zoom_max;
        }

        if ($settings->zoom_min) {
            $app->config['maps.zoom.min'] = $settings->zoom_min;
        }

        if ($settings->latitude && $settings->longitude) {
            $app->config['maps.center'] = [$settings->latitude, $settings->longitude];
        }
    }

    /**
     * @param null|Settings $settings 
     * @param App $app 
     * @return void 
     */
    public function setSocialMedia(?Settings $settings, App $app): void
    {
        if ($settings->socialmediaData) {
            $socialMedia = (array) $settings->socialmediaData;
            foreach ($socialMedia as $metadata => $link) {
                $app->config['social-media'][$metadata] = [
                    'icon' => $metadata,
                    'link' => $link
                ];
            }
        }
    }

    /**
     * @param null|Settings $settings 
     * @param App $app 
     * @return void 
     */
    public function setImagesHome(?Settings $settings, App $app)
    {
        $public_banner_url = null;
        if ($bannerImageData = $settings->bannerImageData) {
            if (file_exists($bannerImageData->path)) {
                $banner_ile =   basename($bannerImageData->path);
                $app->config['module.home']['home-header'] = "img/home/{$banner_ile}";
                $public_banner_url = $app->view->asset("img/home/{$banner_ile}", false);
            }
        }

        $public_opportunity_url = null;
        if ($entitiesOpportunityImageData = $settings->entitiesOpportunityImageData) {
            if (file_exists($entitiesOpportunityImageData->path)) {
                $entities_opportunity_file =   basename($entitiesOpportunityImageData->path);
                $app->config['module.home']['home-opportunities'] = "img/home/{$entities_opportunity_file}";
                $public_opportunity_url = $app->view->asset("img/home/{$entities_opportunity_file}", false);
            }
        }

        $public_event_url = null;
        if ($entitiesEventImageData = $settings->entitiesEventImageData) {
            if (file_exists($entitiesEventImageData->path)) {
                $entities_event_file =   basename($entitiesEventImageData->path);
                $app->config['module.home']['home-events'] = "img/home/{$entities_event_file}";
                $public_event_url = $app->view->asset("img/home/{$entities_event_file}", false);
            }
        }

        $public_space_url = null;
        if ($entitiesSpaceImageData = $settings->entitiesSpaceImageData) {
            if (file_exists($entitiesSpaceImageData->path)) {
                $entities_space_file =   basename($entitiesSpaceImageData->path);
                $app->config['module.home']['home-spaces'] = "img/home/{$entities_space_file}";
                $public_space_url = $app->view->asset("img/home/{$entities_space_file}", false);
            }
        }

        $public_agent_url = null;
        if ($entitiesAgentImageData = $settings->entitiesAgentImageData) {
            if (file_exists($entitiesAgentImageData->path)) {
                $entities_agent_file =   basename($entitiesAgentImageData->path);
                $app->config['module.home']['home-agents'] = "img/home/{$entities_agent_file}";
                $public_agent_url = $app->view->asset("img/home/{$entities_agent_file}", false);
            }
        }

        $public_project_url = null;
        if ($entitiesProjectImageData = $settings->entitiesProjectImageData) {
            if (file_exists($entitiesProjectImageData->path)) {
                $entities_project_file =   basename($entitiesProjectImageData->path);
                $app->config['module.home']['home-projects'] = "img/home/{$entities_project_file}";
                $public_project_url = $app->view->asset("img/home/{$entities_project_file}", false);
            }
        }

        $public_register_url = null;
        if ($registerImageData = $settings->registerImageData) {
            if (file_exists($registerImageData->path)) {
                $entities_register_file =   basename($registerImageData->path);
                $app->config['module.home']['home-register'] = "img/home/{$entities_register_file}";
                $public_register_url = $app->view->asset("img/home/{$entities_register_file}", false);
            }
        }

        $app->view->jsObject['config']['oneClickUploads'] = [
            'home-header' => $public_banner_url,
            'home-opportunities' => $public_opportunity_url,
            'home-events' => $public_event_url,
            'home-spaces' => $public_space_url,
            'home-agents' => $public_agent_url,
            'home-projects' => $public_project_url,
            'home-register' => $public_register_url,
        ];
    }

    /**
     * @param null|Settings $settings 
     * @param App $app 
     * @return void 
     */
    public function setTextsHome(?Settings $settings, App $app)
    {
        if ($bannerTitle = $settings->bannerTitle) {
            $app->config['text:home-header.title'] = $bannerTitle;
        }

        if ($entitiesTitle = $settings->entitiesTitle) {
            $app->config['text:home-entities.title'] = $entitiesTitle;
        }

        if ($entitiesDescription = $settings->entitiesDescription) {
            $app->config['text:home-entities.description'] = $entitiesDescription;
        }

        if ($bannerDescription = $settings->bannerDescription) {
            $app->config['text:home-header.description'] = $bannerDescription;
        }

        if ($entityOpportunityDescription = $settings->entityOpportunityDescription) {
            $app->config['text:home-entities.opportunities'] = $entityOpportunityDescription;
        }

        if ($entityEventDescription = $settings->entityEventDescription) {
            $app->config['text:home-entities.events'] = $entityEventDescription;
        }

        if ($entitySpaceDescription = $settings->entitySpaceDescription) {
            $app->config['text:home-entities.spaces'] = $entitySpaceDescription;
        }

        if ($entityAgentDescription = $settings->entityAgentDescription) {
            $app->config['text:home-entities.agents'] = $entityAgentDescription;
        }

        if ($entityProjectDescription = $settings->entityProjectDescription) {
            $app->config['text:home-entities.projects'] = $entityProjectDescription;
        }

        if ($featureTitle = $settings->featureTitle) {
            $app->config['text:home-feature.title'] = $featureTitle;
        }

        if ($featureDescription = $settings->featureDescription) {
            $app->config['text:home-feature.description'] = $featureDescription;
        }

        if ($registerTitle = $settings->registerTitle) {
            $app->config['text:home-register.title'] = $registerTitle;
        }

        if ($registerDescription = $settings->registerDescription) {
            $app->config['text:home-register.description'] = $registerDescription;
        }

        if ($mapTitle = $settings->mapTitle) {
            $app->config['text:home-map.title'] = $mapTitle;
        }

        if ($mapDescription = $settings->mapDescription) {
            $app->config['text:home-map.description'] = $mapDescription;
        }

        if ($developerTitle = $settings->developerTitle) {
            $app->config['text:home-developers.title'] = $developerTitle;
        }

        if ($developDescription = $settings->developerDescription) {
            $app->config['text:home-developers.description'] = $developDescription;
        }
    }

    /**
     * @param null|Settings $settings 
     * @param App $app 
     * @return void 
     * @throws Exception 
     */
    public function setLogoDefinitions(?Settings $settings, App $app): void
    {
        if ($settings->typeLogoDefinition === 'default') {
            if ($logoDefaultTitle = $settings->logoDefaultTitle) {
                $app->config['logo.title'] = $logoDefaultTitle;
            }

            if ($logoDefaultSubTitle = $settings->logoDefaultSubTitle) {
                $app->config['logo.subtitle'] = $logoDefaultSubTitle;
            }

            $app->config['logo.colors'] = [
                $settings->logoColorPart1 ?: "var(--mc-primary-300)",
                $settings->logoColorPart2 ?: "var(--mc-primary-500)",
                $settings->logoColorPart3 ?: "var(--mc-secondary-300)",
                $settings->logoColorPart4 ?: "var(--mc-secondary-500)",
            ];
        } else {
            $public_logo_url = null;
            if ($imageLogoData = $settings->imageLogoData) {
                $app->config['logo.hideLabel'] = true;
                $logo_image_file =   basename($imageLogoData->path);
                $app->config['logo.image'] = "img/home/{$logo_image_file}";
                $public_logo_url = $app->view->asset("img/home/{$logo_image_file}", false);
            }
            $app->view->jsObject['config']['oneClickUploads']['logo-image'] = $public_logo_url;
        }
    }

    /**
     * @param null|Settings $settings 
     * @param App $app 
     * @return void 
     * @throws Exception 
     */
    public function setFaviconDefinitions(?Settings $settings, App $app): void
    {
        $public_faviconSVG_url = null;
        if ($faviconSvgData = $settings->faviconSvgData) {
            $faviconSVG_image_file =   basename($faviconSvgData->path);
            $app->config['favicon.svg'] = "img/home/{$faviconSVG_image_file}";
            $public_faviconSVG_url = $app->view->asset("img/home/{$faviconSVG_image_file}", false);
            $app->view->jsObject['config']['oneClickUploads']['favicon-svg'] = $public_faviconSVG_url;
        }

        $public_faviconPNG_url = null;
        if ($faviconPngData = $settings->faviconPngData) {
            $faviconPNG_image_file =   basename($faviconPngData->path);
            $app->config['favicon.png'] = "img/home/{$faviconPNG_image_file}";
            $public_faviconPNG_url = $app->view->asset("img/home/{$faviconPNG_image_file}", false);
            $app->view->jsObject['config']['oneClickUploads']['favicon-png'] = $public_faviconPNG_url;
        }
    }

    /**
     * @param null|Settings $settings 
     * @param App $app 
     * @return void 
     * @throws Exception 
     */
    public function setShare(?Settings $settings, App $app): void
    {
        $public_share_url = null;
        if ($shareData = $settings->shareData) {
            if (file_exists($shareData->path)) {
                $share_image_file =   basename($shareData->path);
                $app->config['share.image'] = "img/home/{$share_image_file}";
                $public_share_url = $app->view->asset("img/home/{$share_image_file}", false);
                $app->view->jsObject['config']['oneClickUploads']['share-image'] = $public_share_url;
            }
        }
    }

    /**
     * @param null|Settings $settings 
     * @param App $app 
     * @return void 
     * @throws Exception 
     */
    public function setMailImage(?Settings $settings, App $app): void
    {
        $public_mail_image = null;
        if ($mailImageData = $settings->mailImageData) {
            $mail_image_file =   basename($mailImageData->path);
            $public_mail_image = $app->view->asset("img/{$mail_image_file}", false);
            $app->view->jsObject['config']['oneClickUploads']['mail-image'] = $public_mail_image;
        }
    }

    /**
     * @param null|Settings $settings 
     * @param App $app 
     * @return void 
     */
    public function setColors(?Settings $settings, App $app)
    {
        if ($settings) {
            $cache_id = 'ocCostumizerColors';
            $css = null;
            if ($app->mscache->contains($cache_id)) {
                $css = $app->mscache->fetch($cache_id);
            }

            if (!$css) {
                $css_map = [
                    'primary',
                    'secondary',
                    'seals',
                    'agents',
                    'events',
                    'opportunities',
                    'projects',
                    'spaces',
                ];

                $variable_part = [];
                $root_part = [];

                foreach ($css_map as $var) {
                    $meta = "{$var}Color";
                    $color = $settings->$meta;

                    if ($color) {
                        $variable_part[] = "
                        \$$var-500: $color !default;
                        \$$var-300: lighten(\$$var-500, \$lightness-300) !default;
                        \$$var-700: darken(\$$var-500, \$lightness-700) !default;
                    ";

                        $root_part[] = "
                        --mc-$var-500: #{\$$var-500};
                        --mc-$var-300: #{\$$var-300};
                        --mc-$var-700: #{\$$var-700};
                    ";
                    }
                }


                if (!empty($variable_part) && !empty($root_part)) {
                    $variable_part = implode("\n", $variable_part);
                    $root_part = implode("\n", $root_part);

                    $saas = "
                    @use 'sass:color';

                    // Default lightness deltas
                    \$lightness-300: 25% !default;
                    \$lightness-700: 25% !default;

                    $variable_part

                    :root {
                        $root_part
                    }
                ";

                    $scss_filename = tempnam(sys_get_temp_dir(), 'subsite-') . '.scss';
                    $css_filename = tempnam(sys_get_temp_dir(), 'subsite-') . '.css';


                    file_put_contents($scss_filename, $saas);
                    exec("sass $scss_filename $css_filename --no-source-map");

                    $css = file_get_contents($css_filename);


                    $app->mscache->save($cache_id, $css);
                }
            }

            $app->hook('template(<<*>>.body):after', function () use ($css) {
                echo "
                    <style> $css </style>
                ";
            });
        }
    }




    /**
     * @return Settings 
     */
    public function getSettings(): ?Settings
    {
        $app = App::i();

        $subsiteId = $app->subsite ? $app->subsite->id : null;

        if (!$settings = $app->repo('OneClick\\Settings')->findOneBy(['subsiteId' => $subsiteId])) {
            $settings = $app->repo('OneClick\\Settings')->findOneBy(['id' => 1]);
        }

        return $settings;
    }

    /**
     * @return array 
     */
    public function getFromToGeoFilters(): array
    {
        return [
            'AC' => 12,
            'AL' => 27,
            'AM' => 13,
            'AP' => 16,
            'BA' => 29,
            'CE' => 23,
            'DF' => 53,
            'ES' => 32,
            'GO' => 52,
            'MA' => 21,
            'MG' => 31,
            'MS' => 50,
            'MT' => 51,
            'PA' => 15,
            'PB' => 25,
            'PE' => 26,
            'PI' => 22,
            'PR' => 41,
            'RJ' => 33,
            'RN' => 24,
            'RS' => 43,
            'RO' => 11,
            'RR' => 14,
            'SC' => 42,
            'SE' => 28,
            'SP' => 35,
            'TO' => 17
        ];
    }

    /**
     * @return array 
     */
    public function getFromToGeoDivisionsHierarchy(): array
    {
        return [
            'pais' => i::__('País'),
            'regiao' => i::__('Região'),
            'estado' => i::__('Estado'),
            'mesorregiao' => i::__('Mesorregião'),
            'microrregiao'     => i::__('Microrregião'),
            'municipio' => i::__('Município'),
            'zona' => i::__('Zona'),
            'subprefeitura' => i::__('Subprefeitura'),
            'distrito' => i::__('Distrito'),
            'setor_censitario' => i::__('Setor Censitario')
        ];
    }

    /**
     * @param string $metadata 
     * @return string 
     */
    public function socialmediaLabels(string $metadata): string
    {
        $from_to = [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'linkedin' => 'Linkedin',
            'pinterest' => 'Pinterest',
            'spotify' => 'Spotify',
            'tiktok' => 'Tiktok',
            'twitter' => 'X Twitter',
            'vimeo' => 'Vimeo',
            'youtube' => 'Youtube'
        ];

        return $from_to[$metadata];
    }
}
