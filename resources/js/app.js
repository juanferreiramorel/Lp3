import './bootstrap';
// import 'admin-lte'; // Comentado: AdminLTE se carga desde CDN en app.blade.php
import {
    Alignment,
    BlockQuote,
    Bold,
    ClassicEditor,
    Essentials,
    Font,
    GeneralHtmlSupport,
    Heading,
    Image,
    ImageCaption,
    ImageInsert,
    ImageStyle,
    ImageTextAlternative,
    ImageToolbar,
    ImageUpload,
    Indent,
    IndentBlock,
    Italic,
    Link,
    List,
    Paragraph,
    PasteFromOffice,
    PictureEditing,
    SimpleUploadAdapter,
    SourceEditing,
    Strikethrough,
    Table,
    TableToolbar,
    Underline,
    Undo
} from 'ckeditor5';

import 'ckeditor5/ckeditor5.css';

const contratoPlugins = [
    Essentials, Bold, Italic, Underline, Strikethrough, Font, Paragraph, Heading,
    List, Indent, IndentBlock, Alignment, Link, BlockQuote, Table, TableToolbar,
    PasteFromOffice, GeneralHtmlSupport, SourceEditing, Undo,
    Image, ImageCaption, ImageInsert, ImageStyle, ImageTextAlternative,
    ImageToolbar, ImageUpload, PictureEditing, SimpleUploadAdapter
];

const getToolbarConfig = () => ({
    items: [
        'heading', '|',
        'fontSize', 'fontFamily', '|',
        'bold', 'italic', 'underline', 'strikethrough', '|',
        'alignment', 'blockQuote', '|',
        'numberedList', 'bulletedList', '|',
        'indent', 'outdent', '|',
        'link', 'insertImage', 'uploadImage', '|',
        'insertTable', '|',
        'undo', 'redo', '|',
        'sourceEditing'
    ],
    shouldNotGroupWhenFull: true
});

const getFontSizeConfig = () => ({
    options: [9, 11, 12, 13, 14, 'default', 18, 20, 22, 24, 26, 28, 36, 48]
});

const getFontFamilyConfig = () => ({
    options: [
        'default',
        'Times New Roman, Times, serif',
        'Arial, Helvetica, sans-serif',
        'Courier New, Courier, monospace',
        'Georgia, serif',
        'Lucida Sans Unicode, Lucida Grande, sans-serif',
        'Tahoma, Geneva, sans-serif',
        'Trebuchet MS, Helvetica, sans-serif',
        'Verdana, Geneva, sans-serif'
    ],
    supportAllValues: true
});

const getHeadingConfig = () => ({
    options: [
        { model: 'paragraph', title: 'Párrafo', class: 'ck-heading_paragraph' },
        { model: 'heading1', view: 'h1', title: 'Encabezado 1', class: 'ck-heading_heading1' },
        { model: 'heading2', view: 'h2', title: 'Encabezado 2', class: 'ck-heading_heading2' },
        { model: 'heading3', view: 'h3', title: 'Encabezado 3', class: 'ck-heading_heading3' },
        { model: 'heading4', view: 'h4', title: 'Encabezado 4', class: 'ck-heading_heading4' }
    ]
});

const getImageConfig = () => ({
    styles: ['inline', 'block', 'side'],
    toolbar: [
        'toggleImageCaption',
        'imageTextAlternative',
        '|',
        'imageStyle:inline',
        'imageStyle:block',
        'imageStyle:side'
    ]
});

const getLinkConfig = () => ({
    addTargetToExternalLinks: true,
    defaultProtocol: 'https://'
});

const getTableConfig = () => ({
    contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
});

const getHtmlSupportConfig = () => ({
    allow: [
        {
            name: /.*/,
            attributes: true,
            classes: true,
            styles: true
        }
    ]
});

const getSimpleUploadConfig = () => ({
    uploadUrl: '',
    headers: {}
});

const DEFAULT_LICENSE_KEY = 'GPL';

const buildContratoEditorConfig = (overrides = {}) => {
    const {
        toolbar,
        fontSize,
        fontFamily,
        heading,
        image,
        link,
        table,
        htmlSupport,
        simpleUpload,
        plugins,
        licenseKey,
        ...rest
    } = overrides;

    const config = {
        plugins: plugins || contratoPlugins,
        toolbar: toolbar || getToolbarConfig(),
        fontSize: fontSize || getFontSizeConfig(),
        fontFamily: fontFamily || getFontFamilyConfig(),
        heading: heading || getHeadingConfig(),
        image: image ? { ...getImageConfig(), ...image } : getImageConfig(),
        link: link ? { ...getLinkConfig(), ...link } : getLinkConfig(),
        table: table || getTableConfig(),
        htmlSupport: htmlSupport || getHtmlSupportConfig(),
        simpleUpload: simpleUpload ? { ...getSimpleUploadConfig(), ...simpleUpload } : getSimpleUploadConfig()
    };

    config.licenseKey = licenseKey || DEFAULT_LICENSE_KEY;

    return Object.assign(config, rest);
};

// Expo global para usar en Blade
window.CKEDITOR_CONFIG = {
    Alignment,
    BlockQuote,
    Bold,
    ClassicEditor,
    Essentials,
    Font,
    GeneralHtmlSupport,
    Heading,
    Image,
    ImageCaption,
    ImageInsert,
    ImageStyle,
    ImageTextAlternative,
    ImageToolbar,
    ImageUpload,
    Indent,
    IndentBlock,
    Italic,
    Link,
    List,
    Paragraph,
    PasteFromOffice,
    PictureEditing,
    SimpleUploadAdapter,
    SourceEditing,
    Strikethrough,
    Table,
    TableToolbar,
    Underline,
    Undo
};

window.CKEDITOR_CONTRATO = {
    buildConfig: buildContratoEditorConfig,
    plugins: contratoPlugins
};
