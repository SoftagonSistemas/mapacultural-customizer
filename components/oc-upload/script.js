app.component('oc-upload', {
    template: $TEMPLATES['oc-upload'],
    emits: ['setFile'],

    mounted() {
        window.addEventListener('resetPreviewImage', this.resetPreviewImage);
    },

    setup() {
        const text = Utils.getTexts('oc-upload')
        const messages = useMessages();
        return { text, messages }
    },

    props: {
        entity: {
            type: Entity,
            required: true
        },
        uploadOnSubmit: {
            type: Boolean,
            default: true,
        },
        prop: {
            type: String,
            require: true
        },
        dir: {
            type: [Boolean, String],
            default: false
        },
        ext: {
            type: String,
            default: 'jpg'
        },
        imageSize: {
            type: Array,
            default: [1170, 390]
        },
        imageFinalName: {
            type: [Boolean, String],
            default: false
        },
    },

    data() {
        return {
            newFile: {},
            previewImage: $MAPAS.config.oneClickUploads[this.prop] || null,
            errorHandlerImage: null

        };
    },

    methods: {
        setFile(event) {
            const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
            const MAX_WIDTH = this.imageSize[0];
            const MAX_HEIGHT = this.imageSize[1];

            const file = event.target.files[0];

            if (file) {
                if (file.size > MAX_FILE_SIZE) {
                    this.messages.error(this.text('maxSizeErrorSizeImage'));
                    this.resetFileInput(event);
                    return;
                }

                const image = new Image();
                const reader = new FileReader();

                reader.onload = (e) => {
                    image.onload = () => {
                        if (image.width > MAX_WIDTH || image.height > MAX_HEIGHT) {
                            this.messages.error(this.text('sizeErrorSizeImage') + `${MAX_WIDTH}x${MAX_HEIGHT}px.`);
                            this.resetFileInput(event);
                        } else {
                            this.newFile = file;
                            this.previewImage = e.target.result;
                            this.upload();

                            this.$emit('setFile', this.newFile);
                        }
                    };
                    image.src = e.target.result;
                };

                reader.readAsDataURL(file);
            }
        },

        // Método auxiliar para resetar o input
        resetFileInput(event) {
            this.newFile = null;
            this.previewImage = null; // Limpa o preview
            event.target.value = ""; // Reseta o valor do input
        },

        resetPreviewImage() {
            this.previewImage = null;
            $MAPAS.config.oneClickUploads[this.prop] = null
        },


        async upload() {
            const api = new API();
            const url = Utils.createUrl('settings', 'upload', [this.entity.id]);
            const data = new FormData();


            if(this.imageFinalName) {
                data.append('imageFinalName', this.imageFinalName);
            }

            data.append('ocFileUpload', this.newFile);
            data.append('prop', this.prop);
            data.append('ext', this.ext);

            if (this.dir) {
                data.append('dir', this.dir);
            }

            const res = await fetch(url, { method: 'POST', body: data });

            if (!res.ok) {
                throw new Error(`Erro: ${res.status}`);
            }

            const responseData = await res.json();
            if (responseData) {
                this.newFile = {};
                this.$refs.file.value;
                this.messages.success(this.text('successUpload'));
            } else {
                this.messages.error(this.text('errorUpload'));
            }

        },
        async submit(modal) {
            if (this.uploadOnSubmit) {
                await this.upload(modal);
            } else {
                modal.close();
            }
        },

        async doPromise(res, cb) {
            let data = await res.json();
            let result;

            if (res.ok) { // status 20x
                data = cb(data) || data;
                result = Promise.resolve(data);
            } else {
                this.catchErrors(res, data);
                data.status = res.status;
                result = Promise.reject(data);
            }

            this.__processing = false;
            return result;
        }
    }
});
