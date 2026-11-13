<script>
    (function() {
        const initSummernote = () => {
            if (typeof $ === 'undefined' || typeof $.fn.summernote === 'undefined') {
                return;
            }

            $('.summernote').summernote({
                height: 350,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['codeview']]
                ]
            });
        };

        const initSlugAutoFill = () => {
            const titleInput = document.querySelector('[data-slug-source]');
            const slugInput = document.querySelector('[data-slug-target]');

            if (!titleInput || !slugInput) {
                return;
            }

            const slugify = (value) => value
                .toString()
                .toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .substring(0, 120);

            let manualOverride = slugInput.value.trim().length > 0;

            slugInput.addEventListener('input', () => {
                manualOverride = slugInput.value.trim().length > 0;
            });

            titleInput.addEventListener('input', () => {
                if (!manualOverride) {
                    slugInput.value = slugify(titleInput.value);
                }
            });
        };

        const initCategoryLabelSync = () => {
            const select = document.getElementById('blog_category_id');
            const labelInput = document.getElementById('category_label');

            if (!select || !labelInput) {
                return;
            }

            const sync = () => {
                const selected = select.selectedOptions[0];
                labelInput.value = selected && selected.value ? selected.text.trim() : '';
            };

            select.addEventListener('change', sync);
            sync();
        };

        document.addEventListener('DOMContentLoaded', () => {
            initSummernote();
            initSlugAutoFill();
            initCategoryLabelSync();
        });
    })();
</script>
