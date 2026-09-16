<div class="mb-0">
                    <label for="forumFilterImagesCount" class="form-label small">
                        Кол-во изображений
                        <span id="forumFilterImagesCountValue" class="ms-2 fw-bold text-primary">1</span>
                    </label>
                    <input type="range" class="form-range" id="forumFilterImagesCount" data-filter-url="/index.php?r=site%2Fpublications" min="0" max="100" value="1">
                    <div class="form-text">0 — без ограничения</div>
                </div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const slider = document.getElementById('forumFilterImagesCount');
        const valueDisplay = document.getElementById('forumFilterImagesCountValue');

        function updateDisplay(value) {
            valueDisplay.textContent = value == 0 ? '∞' : value;
        }

        // Initial display
        updateDisplay(slider.value);

        // Real-time update on input
        slider.addEventListener('input', function () {
            updateDisplay(this.value);
        });
    });
</script>