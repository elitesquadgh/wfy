<table style='width:100%;margin-bottom:20px'>
    <tr>
        <td>
            <img src='/{$image}' style="width:30px;height:30px"/> 
            <span style='font-size:xx-large;font-weight:bold;padding:5px'>{$title}</span>
        </td>
        <td style = 'font-size:x-small;text-align:right'>
            {section name=line loop=$address}{$address[line]}<br/>{/section}
        </td>
    </tr>
</table>