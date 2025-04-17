<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    entity-field
    oc-dialog
 ');

?>

<div class="settings-recaptcha">
    <oc-dialog>
        <template #content>
            <?= i::__('Configure aqui as credenciais do Google reCAPTCHA para proteger seu site contra acessos automáticos.') ?>
            <?= i::__('Solicite ao responsável de TI a chave do site e a chave secreta fornecidas pelo Google.') ?>
        </template>
    </oc-dialog>
    <div class="grid-12">
        <div class="col-6 recaptcha-settings-filds">
            <entity-field :entity="entity" prop="isRecaptchaActive" @click="entity.save()" class="col-6"></entity-field>
            <entity-field :entity="entity" prop="recaptcha_sitekey" class="col-6"></entity-field>
            <entity-field :entity="entity" prop="recaptcha_secret" class="col-6"></entity-field>
        </div>
        <div class="col-6">
            <i><small><?= i::__('Se tudo correr bem, ao recarregar a página, o reCAPTCHA irá aparecer aqui. Basta, então, responder <b>Sim</b> à pergunta <b>Ativar reCAPTCHA em todo o site?</b> para que ele seja ativado para as demais páginas.') ?></small></i> <br><br>
            <VueRecaptcha :sitekey="entity.recaptcha_sitekey" class="g-recaptcha"></VueRecaptcha>
        </div>
    </div>

    <div class="btn-entity-actions">
        <oc-actions :entity="entity" editable>
            <template #default="{actions}">
                <button class="button button--primary" @click="saveRecaptcha(actions)"><span><?= i::__('Salvar e recarregar') ?></span></button>
            </template>
        </oc-actions>
    </div>
</div>