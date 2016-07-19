<link href="media/script/air-datepicker/css/datepicker.min.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="media/script/air-datepicker/datepicker.min.js"></script>
<script src="media/script/air-datepicker/i18n/datepicker.ja.js"></script>
<script type="text/javascript">

var start = new Date();
start.setHours(0);
start.setMinutes(0);

var options = {
    language      : '[(lang_code)]',
    timepicker    : true,
    todayButton   : new Date(),
    keyboardNav   : false,
    startDate     : start,
    autoClose     : true,
    toggleSelected: false,
    clearButton   : true,
    minutesStep   : 5,
    dateFormat    : '[(datetime_format:strtolower)]',
    onSelect      : function (fd, d, picker) {
        documentDirty = true;
    },
    navTitles: {
       days: 'yyyy/mm'
    }
};

var pub_date   = jQuery('#pub_date');
var unpub_date = jQuery('#unpub_date');
var dob        = jQuery('#dob');
var datefrom   = jQuery('#datefrom');
var dateto     = jQuery('#dateto');
var blockedafter = jQuery('#blockedafter');
var blockeduntil = jQuery('#blockeduntil');

pub_date.datepicker(options);
if(pub_date.val()) {
    pub_date_val = pub_date.val();
    if(pub_date_val.indexOf('-')) pub_date_val = pub_date_val.replace(/(\d+)\-(\d+)\-(\d+)(.*)/g , "$3/$2/$1$4");
    pub_date.data('datepicker').selectDate(new Date(pub_date_val));
    documentDirty = false;
}

unpub_date.datepicker(options);
if(unpub_date.val()) {
    unpub_date_val = unpub_date.val();
    if(unpub_date_val.indexOf('-')) unpub_date_val = unpub_date_val.replace(/(\d+)\-(\d+)\-(\d+)(.*)/g , "$3/$2/$1$4");
    unpub_date.data('datepicker').selectDate(new Date(unpub_date_val));
    documentDirty = false;
}

dob.datepicker(options);
if(dob.val()) {
    dob_val = dob.val();
    if(dob_val.indexOf('-')) dob_val = dob_val.replace(/(\d+)\-(\d+)\-(\d+)(.*)/g , "$3/$2/$1$4");
    dob.data('datepicker').selectDate(new Date(dob_val));
    documentDirty = false;
}

datefrom.datepicker(options);
if(datefrom.val()) {
    datefrom_val = datefrom.val();
    if(datefrom_val.indexOf('-')) datefrom_val = datefrom_val.replace(/(\d+)\-(\d+)\-(\d+)(.*)/g , "$3/$2/$1$4");
    datefrom.data('datepicker').selectDate(new Date(datefrom_val));
    documentDirty = false;
}

dateto.datepicker(options);
if(dateto.val()) {
    dateto_val = dateto.val();
    if(dateto_val.indexOf('-')) dateto_val = dateto_val.replace(/(\d+)\-(\d+)\-(\d+)(.*)/g , "$3/$2/$1$4");
    dateto.data('datepicker').selectDate(new Date(dateto_val));
    documentDirty = false;
}

blockedafter.datepicker(options);
if(blockedafter.val()) {
    blockedafter_val = blockedafter.val();
    if(blockedafter_val.indexOf('-')) blockedafter_val = blockedafter_val.replace(/(\d+)\-(\d+)\-(\d+)(.*)/g , "$3/$2/$1$4");
    blockedafter.data('datepicker').selectDate(new Date(blockedafter_val));
    documentDirty = false;
}

blockeduntil.datepicker(options);
if(blockeduntil.val()) {
    blockeduntil_val = blockeduntil.val();
    if(blockeduntil_val.indexOf('-')) blockeduntil_val = blockeduntil_val.replace(/(\d+)\-(\d+)\-(\d+)(.*)/g , "$3/$2/$1$4");
    blockeduntil.data('datepicker').selectDate(new Date(blockeduntil_val));
    documentDirty = false;
}

</script>
