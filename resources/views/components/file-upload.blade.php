@props(['relacionadoTipo' => null, 'relacionadoId' => null])

<div class="r-card-flat">
    <h2 class="r-label" style="margin-bottom:var(--space-4);">Subir archivo</h2>

    <form method="POST"
          action="{{ route('archivos.store') }}"
          enctype="multipart/form-data"
          class="r-stack">
        @csrf

        @if($relacionadoTipo)
            <input type="hidden" name="relacionado_tipo" value="{{ $relacionadoTipo }}">
        @endif
        @if($relacionadoId)
            <input type="hidden" name="relacionado_id" value="{{ $relacionadoId }}">
        @endif

        <div>
            <input type="file"
                   name="archivo"
                   id="archivo-input"
                   accept="image/jpeg,image/png,image/webp,application/pdf"
                   class="r-input"
                   style="padding:8px;cursor:pointer;">
            <p class="r-help">JPG, PNG, WEBP o PDF. Máx. 10 MB.</p>
            @error('archivo') <p class="r-error">{{ $message }}</p> @enderror
        </div>

        <div id="archivo-preview" hidden>
            <div class="r-flex r-items-center r-gap-3" style="padding:var(--space-3);background:var(--color-paper);border:1px solid var(--color-line);border-radius:var(--border-radius-sm);">
                <img id="preview-img" hidden style="height:64px;width:64px;object-fit:cover;border-radius:8px;" alt="Vista previa del archivo">
                <div id="preview-pdf" hidden style="display:flex;align-items:center;justify-content:center;height:64px;width:64px;background:var(--color-danger-soft);border-radius:8px;">
                    <span class="r-mono" style="font-size:0.75rem;font-weight:700;color:var(--color-danger);">PDF</span>
                </div>
                <div class="r-grow" style="min-width:0;">
                    <p id="preview-nombre" style="font-size:0.9rem;color:var(--color-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:0;"></p>
                    <p id="preview-tamano" class="r-caption" style="text-transform:none;letter-spacing:0;margin:2px 0 0;"></p>
                </div>
                <button type="button" id="quitar-archivo" class="r-btn r-btn-ghost r-btn-sm" aria-label="Quitar archivo seleccionado">Quitar</button>
            </div>
        </div>

        <button type="submit" id="btn-subir" class="r-btn r-btn-primary r-btn-sm" style="align-self:flex-start;">
            Subir archivo
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('archivo-input');
    const preview = document.getElementById('archivo-preview');
    const previewImg = document.getElementById('preview-img');
    const previewPdf = document.getElementById('preview-pdf');
    const previewNombre = document.getElementById('preview-nombre');
    const previewTamano = document.getElementById('preview-tamano');
    const quitarBtn = document.getElementById('quitar-archivo');

    input.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) { preview.hidden = true; return; }

        previewNombre.textContent = file.name;
        previewTamano.textContent = formatBytes(file.size);

        if (file.type.startsWith('image/')) {
            previewImg.hidden = false;
            previewPdf.hidden = true;
            const reader = new FileReader();
            reader.onload = (e) => { previewImg.src = e.target.result; };
            reader.readAsDataURL(file);
        } else if (file.type === 'application/pdf') {
            previewImg.hidden = true;
            previewPdf.hidden = false;
        }

        preview.hidden = false;
    });

    quitarBtn.addEventListener('click', function () {
        input.value = '';
        preview.hidden = true;
    });

    function formatBytes(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' B';
    }
});
</script>
