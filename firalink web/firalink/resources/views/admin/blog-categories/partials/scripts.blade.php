<script>
    document.addEventListener('DOMContentLoaded', function() {
        const nameInput = document.querySelector('[data-slug-source]');
        const slugInput = document.querySelector('[data-slug-target]');

        if (nameInput && slugInput) {
            let manualSlug = slugInput.value.trim().length > 0;

            slugInput.addEventListener('input', function() {
                manualSlug = this.value.trim().length > 0;
            });

            nameInput.addEventListener('input', function() {
                if (!manualSlug) {
                    slugInput.value = this.value
                        .toString()
                        .toLowerCase()
                        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '')
                        .substring(0, 140);
                }
            });
        }
    });
</script>
