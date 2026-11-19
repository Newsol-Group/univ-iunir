// https://stackoverflow.com/questions/15900485/correct-way-to-convert-size-in-bytes-to-kb-mb-gb-in-javascript
// https://jstool.gitlab.io/babel-es6-to-es5/
function formatBytes(bytes) {
    var decimals = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 2;
    if (!+bytes) {
        return '0 Bytes';
    }
    var k = 1000;
    var dm = decimals < 0 ? 0 : decimals;
    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return "".concat(parseFloat((bytes / Math.pow(k, i)).toFixed(dm)), " ").concat(sizes[i]);
}

function init_styling_file_inputs() {
    // https://dev.to/ibn_abubakre/styling-file-inputs-like-a-boss-mhm
    var file = document.querySelector('#file');
    // Si existe el input con el id #file
    if (file !== null){
        file.addEventListener('change', function (e) {
            // Get the selected file
            var [file] = e.target.files;
            
            // Get the file name and size
            var fileName = file.name;
            var bytes = file.size;
            
            // Format size
            var fileSize = formatBytes(bytes, 1);
            // Set the text content
            var fileNameAndSize = fileName + ' - ' + fileSize;
            document.querySelector('.file-name').textContent = fileNameAndSize;
        });
    }
}

$(document).ready(function () {
    // executes when HTML-Document is loaded and DOM is ready
    init_styling_file_inputs();
});