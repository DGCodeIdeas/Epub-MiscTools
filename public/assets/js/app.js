// GlyphShifter Frontend Logic

const apiClient = {
    async post(endpoint, data) {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return response.json();
    },
    async uploadChunk(uploadId, chunkIndex, totalChunks, chunkData) {
        const formData = new FormData();
        formData.append('uploadId', uploadId);
        formData.append('chunkIndex', chunkIndex);
        formData.append('totalChunks', totalChunks);
        formData.append('chunkData', chunkData);

        const response = await fetch('/api/upload/chunk', {
            method: 'POST',
            body: formData
        });
        return response.json();
    }
};

class ChunkUploader {
    constructor(file, progressCallback) {
        this.file = file;
        this.progressCallback = progressCallback;
        this.chunkSize = 2 * 1024 * 1024; // 2MB
        this.totalChunks = Math.ceil(file.size / this.chunkSize);
    }

    async upload() {
        const init = await apiClient.post('/api/upload/init', { fileName: this.file.name });
        const uploadId = init.uploadId;

        for (let i = 0; i < this.totalChunks; i++) {
            const start = i * this.chunkSize;
            const end = Math.min(start + this.chunkSize, this.file.size);
            const chunk = this.file.slice(start, end);

            await apiClient.uploadChunk(uploadId, i, this.totalChunks, chunk);
            if (this.progressCallback) {
                this.progressCallback(Math.round(((i + 1) / this.totalChunks) * 100));
            }
        }

        return uploadId;
    }
}

$(document).ready(function() {
    let epubUploadId = null;
    let fontUploadId = null;

    function checkReady() {
        $('#process-btn').prop('disabled', !(epubUploadId && fontUploadId));
    }

    // EPUB Upload
    $('#epub-dropzone').click(() => $('#epub-input').click());
    $('#epub-input').change(async function(e) {
        const file = e.target.files[0];
        if (!file) return;

        $('#epub-name').text(`Uploading: ${file.name}`);
        $('#epub-progress').removeClass('hidden');

        try {
            const uploader = new ChunkUploader(file, (percent) => {
                $('#epub-progress .progress-bar').css('width', `${percent}%`);
            });
            epubUploadId = await uploader.upload();
            $('#epub-name').text(`Ready: ${file.name} ✅`);
            checkReady();
        } catch (err) {
            alert('EPUB upload failed: ' + err.message);
        }
    });

    // Font Upload
    $('#font-dropzone').click(() => $('#font-input').click());
    $('#font-input').change(async function(e) {
        const file = e.target.files[0];
        if (!file) return;

        $('#font-name').text(`Uploading: ${file.name}`);
        $('#font-progress').removeClass('hidden');

        try {
            const uploader = new ChunkUploader(file, (percent) => {
                $('#font-progress .progress-bar').css('width', `${percent}%`);
            });
            fontUploadId = await uploader.upload();
            $('#font-name').text(`Ready: ${file.name} ✅`);
            checkReady();
        } catch (err) {
            alert('Font upload failed: ' + err.message);
        }
    });

    // Process
    $('#process-btn').click(async function() {
        $(this).prop('disabled', true).text('Processing...');

        try {
            const result = await apiClient.post(`/api/tools/${TOOL_SLUG}/process`, {
                epubUploadId,
                fontUploadId
            });

            if (result.status === 'success') {
                $('#upload-container').addClass('hidden');
                $('#result-container').removeClass('hidden');
                $('#download-link').attr('href', result.downloadUrl);
            } else {
                throw new Error(result.message || 'Processing failed');
            }
        } catch (err) {
            alert('Processing failed: ' + err.message);
            $(this).prop('disabled', false).text('Process EPUB');
        }
    });
});
