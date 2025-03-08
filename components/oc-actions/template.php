<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;
$clearUrl = $app->createUrl('settings', 'clearcache');
?>

<div class="oc-actions" v-if="useActions">
    <a v-if="clearCache" href="<?=$clearUrl?>" class="button button--primary"><span><?= i::__('Apagar cache') ?></span></a>
    <button class="button button--primary" @click="save()"><span><?= i::__('Salvar') ?></span></button>
</div>