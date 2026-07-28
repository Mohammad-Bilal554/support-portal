<?php
/**
 * Reusable Modal Partials
 *
 * Include at bottom of any page that needs modals.
 * Individual modals can be triggered via JS or data-bs-toggle.
 */
?>

<!-- ── Generic Confirm Modal ────────────────────────────────── -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-bold" id="confirmModalTitle">Confirm Action</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1 pb-2">
                <p class="text-muted mb-0" style="font-size:.875rem;" id="confirmModalMessage">
                    Are you sure you want to perform this action?
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="confirmModalBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- ── Image Preview Modal ───────────────────────────────────── -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background:transparent;border:none;box-shadow:none;">
            <div class="modal-header border-0" style="position:absolute;top:0;right:0;z-index:1;">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 text-center">
                <img src="" id="imagePreviewSrc" style="max-width:100%;max-height:80vh;border-radius:12px;">
            </div>
        </div>
    </div>
</div>

<script>
// Image preview trigger
document.addEventListener('click', function(e) {
    const img = e.target.closest('[data-preview-image]');
    if (!img) return;
    e.preventDefault();
    document.getElementById('imagePreviewSrc').src = img.dataset.previewImage || img.src;
    new bootstrap.Modal(document.getElementById('imagePreviewModal')).show();
});
</script>
