app.component('oc-reset-default-values', {
    template: $TEMPLATES['oc-reset-default-values'],

    props: {
        entity: {
            type: Entity,
            required: true
        },
        prop: {
            type: String,
            required: true
        },
    },
    data() {
        return {}
    },
    methods: {
        reset() {
            let prop = $MAPAS.fromToFilesMetadata[this.prop];
            window.dispatchEvent(new CustomEvent('resetPreviewImage', { detail: { ref: this.prop } }));
            this.entity[prop] = null;
            this.entity.save();
        }
    }
});
