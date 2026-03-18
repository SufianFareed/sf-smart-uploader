jQuery(function($){

let files = JSON.parse(localStorage.getItem('sf_files') || '[]');

function syncInput(){
    $('#sf_files').val(JSON.stringify(files));
}

// restore on load
$(document).ready(function(){
    if(files.length){
        files.forEach(f => renderFile(f));
        syncInput();
    }
});

// upload button click
$('#sf-upload-btn').on('click', function(){
    $('#sf-file-input').click();
});

// file select
$('#sf-file-input').on('change', function(){
    handleFiles(this.files);
});

function handleFiles(fileList){

    if(files.length + fileList.length > sf_ajax.max_files){
        alert('Max ' + sf_ajax.max_files + ' files allowed');
        return;
    }

    $.each(fileList, function(i, file){

        let formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'sf_upload');

        $.ajax({
            url: sf_ajax.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,

            success: function(res){

                let fileData = res.data;

                files.push(fileData);
                localStorage.setItem('sf_files', JSON.stringify(files));

                renderFile(fileData);
                syncInput();
            }
        });

    });
}

// render preview
function renderFile(file){

    let html = `<div class="sf-item">
        ${getPreview(file)}
        <span class="sf-remove">×</span>
    </div>`;

    let el = $(html);

    el.find('.sf-remove').click(function(){
        files = files.filter(f => f.url !== file.url);
        localStorage.setItem('sf_files', JSON.stringify(files));
        el.remove();
        syncInput();
    });

    $('#sf-preview').append(el);
}

function getPreview(file){

    if(file.url.match(/\.(jpg|jpeg|png)$/i)){
        return `<img src="${file.url}" />`;
    } 
    else if(file.url.match(/\.pdf$/i)){
        return `<div class="sf-pdf">PDF</div>`;
    } 
    else {
        return `<div class="sf-file">${file.name}</div>`;
    }
}

// clear AFTER successful order only
$('form.checkout').on('checkout_place_order', function(){
    localStorage.removeItem('sf_files');
});

});