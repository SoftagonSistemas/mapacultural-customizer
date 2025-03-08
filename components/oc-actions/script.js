app.component('oc-actions', {
    template: $TEMPLATES['oc-actions'],
    emits: ['save'],
    setup() {
        const text = Utils.getTexts('evaluation-actions')
        const globalState = useGlobalState();
        return { text, globalState }
    },

    mounted() {
        window.addEventListener('useActions', this.changeUseActions);
    },
    props: {
        entity: {
            type: Entity,
            required: true
        },
        reloadTime: {
            type: [Boolean, Number],
            default: false
        },
        clearCache: {
            type: Boolean,
            default: false
        },
    },
    data() {
        let useActions = this.globalState.useActions === 'nouse-global' ? true : this.globalState.useActions;
        
        return {
            useActions: useActions
        }
    },
    methods: {
        changeUseActions(data) {
            this.useActions = data.detail.useActions;
        },
        save() {
            

            this.entity.save();
            this.$emit('save');

            if(this.reloadTime) {
                setTimeout(() => {
                    window.location.reload();
                }, this.reloadTime);
            }
        },
        async clearCacheExec() {
            const api = new API();
            const url = Utils.createUrl('settings', 'clearCache', [this.entity.id]);
            const data = new FormData();


            const res = await fetch(url, { method: 'POST', body: data });

            if (!res.ok) {
                throw new Error(`Erro: ${res.status}`);
            }

            const responseData = await res.json();
            if (responseData) {
                console.log(responseData)
            } else {
            }
        }
        
    }
});
