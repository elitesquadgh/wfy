<div id="report-wrapper">
    <div id="report-toolbar">
        <ul id="report-export"><li id="report-toolbar-pdf" onclick='exportReport("pdf", true)'>Export PDF</li><li id="report-toolbar-xls" onclick='exportReport("xls", false)'>Export Excel</li></ul><ul id="report-options" onclick='$("#report-filter").fadeToggle()'><li id="report-options-button">Report Options</li></ul>
    </div>
    <div id='report-body-wrapper'>
        <div id="report-body">
        </div>
    </div>
    <div id="report-filter">
        <div id='report-filter-form'>{$filters}</div>
        <div id='report-filter-actions'>
            <input type='button' value='Update Report' class='fapi-submit' onclick="updateReports()" />
        </div>
    </div>
</div>
<script type="text/javascript">
    var reportParams = '';
    function updateReports()
    {
        reportParams = $('#report-filter-form > form').serialize();
        $('#report-body').load("{$path}/generate/?report_format=html&title=no&logo=no&" + reportParams);
        $('#report-filter').fadeOut();
    }
    function exportReport(format, newWindow)
    {
        if(newWindow)
        {
            window.open(buildUrl(format));
        }
        else
        {
            document.location = buildUrl(format);
        }
    }
    
    function buildUrl(format)
    {
        return "{$path}/generate/?report_format=" + format + "&" + reportParams;
    }
    $(function(){ updateReports(); });
</script>