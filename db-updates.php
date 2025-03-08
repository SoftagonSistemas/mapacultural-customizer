<?php

use function MapasCulturais\__exec;
use function MapasCulturais\__table_exists;
use function MapasCulturais\__try;

return [
    'create table settings' => function () {
        if (!__table_exists('settings')) {
            __exec("
                CREATE TABLE settings (
                    id INT NOT NULL, 
                    status SMALLINT NOT NULL, 
                    metadata JSON DEFAULT '{}' NOT NULL, 
                    create_timestamp TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                    update_timestamp TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                    subsite_id SMALLINT NULL, 
                PRIMARY KEY(id))
            ");
        }
        if (!__table_exists('settings_meta')) {
            __exec("
                CREATE TABLE settings_meta (
                    object_id integer NOT NULL,
                    key character varying(32) NOT NULL,
                    value text,
                    id integer NOT NULL
                );
            ");
        }
    },
    'create table settings sequence' => function () {
        __exec("CREATE SEQUENCE oc_settings_id_seq INCREMENT BY 1 MINVALUE 1 START 1");
        __exec("CREATE SEQUENCE settings_meta_id_seq INCREMENT BY 1 MINVALUE 1 START 1");
    },
    'insert default values' => function () {
        // Settings inicial
        __exec("INSERT INTO settings (id, status, metadata, create_timestamp, update_timestamp, subsite_id) VALUES (nextval('oc_settings_id_seq'::regclass), 1, '{}', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, null)");

        // Email
        __exec("INSERT INTO settings_meta (id, key, value, object_id) VALUES (nextval('settings_meta_id_seq'::regclass), 'mailer_email', 'sysadmin@localhost', 1)");
        __exec("INSERT INTO settings_meta (id, key, value, object_id) VALUES (nextval('settings_meta_id_seq'::regclass), 'mailer_host', 'mailhog', 1)");
        __exec("INSERT INTO settings_meta (id, key, value, object_id) VALUES (nextval('settings_meta_id_seq'::regclass), 'mailer_protocol', 'LOCAL', 1)");

        // reCaptcha
        __exec("INSERT INTO settings_meta (id, key, value, object_id) VALUES (nextval('settings_meta_id_seq'::regclass), 'recaptcha_secret', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe', 1)");
        __exec("INSERT INTO settings_meta (id, key, value, object_id) VALUES (nextval('settings_meta_id_seq'::regclass), 'recaptcha_sitekey', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI', 1)");
        __exec("INSERT INTO settings_meta (object_id, \"key\", value, id) VALUES
            (1, 'logoDefaultTitle', 'Mapas', nextval('settings_meta_id_seq'::regclass)),
            (1, 'logoDefaultSubTitle', 'Culturais', nextval('settings_meta_id_seq'::regclass)),
            (1, 'primaryColor', '#117c83', nextval('settings_meta_id_seq'::regclass)),
            (1, 'secondaryColor', '#d14526', nextval('settings_meta_id_seq'::regclass)),
            (1, 'opportunitiesColor', '#d14426', nextval('settings_meta_id_seq'::regclass)),
            (1, 'agentsColor', '#ef7b45', nextval('settings_meta_id_seq'::regclass)),
            (1, 'eventsColor', '#9c4ec7', nextval('settings_meta_id_seq'::regclass)),
            (1, 'spacesColor', '#538d08', nextval('settings_meta_id_seq'::regclass)),
            (1, 'projectsColor', '#107c83', nextval('settings_meta_id_seq'::regclass)),
            (1, 'sealsColor', '#471363', nextval('settings_meta_id_seq'::regclass)),
            (1, 'logoColorPart1', '#2fd9e4', nextval('settings_meta_id_seq'::regclass)),
            (1, 'logoColorPart2', '#107c83', nextval('settings_meta_id_seq'::regclass)),
            (1, 'logoColorPart3', '#ea9e8c', nextval('settings_meta_id_seq'::regclass)),
            (1, 'logoColorPart4', '#d14426', nextval('settings_meta_id_seq'::regclass)),
            (1, 'zoom_default', '5', nextval('settings_meta_id_seq'::regclass)),
            (1, 'zoom_max', '22', nextval('settings_meta_id_seq'::regclass)),
            (1, 'zoom_min', '0', nextval('settings_meta_id_seq'::regclass))
        ");
    },
];
