@props(['relacionadoTipo' => null, 'relacionadoId' => null])

<div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Subir archivo</h2>

    <form method="POST"
          action="{{ route('archivos.store') }}"
          enctype="multipart/form-data"
          class="space-y-4">
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
                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900 dark:file:text-indigo-300">
            <p class="mt-1 text-xs text-gray-500">JPG, PNG, WEBP o PDF. Máx. 10 MB.</p>
            @error('archivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div id="archivo-preview" class="hidden">
            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <img id="preview-img" class="hidden h-16 w-16 object-cover rounded" alt="Preview">
                <div id="preview-pdf" class="hidden flex items-center justify-center h-16 w-16 bg-red-100 dark:bg-red-900/50 rounded">
                    <span class="text-xs font-bold text-red-600 dark:text-red-400">PDF</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p id="preview-nombre" class="text-sm text-gray-800 dark:text-gray-100 truncate"></p>
                    <p id="preview-tamano" class="text-xs text-gray-500 dark:text-gray-400"></p>
                </div>
                <button type="button" id="quitar-archivo"
                        class="text-red-500 hover:text-red-700 text-xs">✕</button>
            </div>
        </div>

        <button type="submit"
                id="btn-subir"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
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
        if (!file) {
            preview.classList.add('hidden');
            return;
        }

        previewNombre.textContent = file.name;
        previewTamano.textContent = formatBytes(file.size);

        if (file.type.startsWith('image/')) {
            previewImg.classList.remove('hidden');
            previewPdf.classList.add('hidden');
            const reader = new FileReader();
            reader.onload = (e) => { previewImg.src = e.target.result; };
            reader.readAsDataURL(file);
        } else if (file.type === 'application/pdf') {
            previewImg.classList.add('hidden');
            previewPdf.classList.remove('hidden');
        }

        preview.classList.remove('hidden');
    });

    quitarBtn.addEventListener('click', function () {
        input.value = '';
        preview.classList.add('hidden');
    });

    function formatBytes(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' B';
    }
});
</script>
