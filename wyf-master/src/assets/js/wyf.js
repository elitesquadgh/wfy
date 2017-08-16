/**
 * Main package for the entire WYF javascript file.
 */

var wyf = {
    getMulti : function(params, callback)
    {
      $.getJSON('/system/api/get_multi?params=' + escape(JSON.stringify(params)),
        function(response){
          if(typeof callback === 'function') callback(response);
        }
      );
    },
    
    openWindow : function(location)
    {
      window.open(location);
    },

    showUploadedData : function(data)
    {
      $("#import-preview").html(data);
    },
    
    updateFilter: function(table, model, value)
    {
        if(value == 0)
        {
          externalConditions[table] = "";
        }
        else
        {
          externalConditions[table] = model + "=" + value;
        }
        window[table + 'Search']();
    },

    confirmRedirect:function(message,path)
    {
      if(confirm(message))
      {
        document.location=path;
      }
    },
	
    init:function()
    {
      wyf.tapi.init();
      setTimeout(
        function(){
          $.getJSON(
            '/system/notifications/get/' + Math.floor(Math.random() * 100000),
            function(response)
            {
              for(var i in response)
              {
                wyf.notifications.show(response[i]);
              }
            }
          );        
        },
        1500
      );
    },

    tapi:
    {	
        tables: new Object(),
        tableIds: new Array(),
        checkedRows: new Array(),
        activity : null,
		
        addTable: function(id,obj)
        {
          if(obj.hardConditions)
          {
              obj.conditions = obj.hardConditions;
          }

          wyf.tapi.tableIds.push(id);
          wyf.tapi.tables[id] = obj;
          wyf.tapi.tables[id].prevPage = 0;
        },
		
        init:function()
        {
          for(var i=0; i < wyf.tapi.tableIds.length; i++)
          {
            var id = wyf.tapi.tableIds[i];  
            wyf.tapi.render(wyf.tapi.tables[id]);
          }
        },
		
        render:function(table)
        {
            var urlParams = "params=" + escape(JSON.stringify(table));
            
            try{
                wyf.tapi.activity.abort();
            }
            catch(e)
            {
                
            }

            wyf.tapi.activity = $.ajax({
                type:"POST",
                url:table.url,
                dataType:"json",
                data:urlParams,
                success:function(r)
                {
                    $("#"+table.id+">tbody").html(r.tbody);
                    $("#"+table.id+"Footer").html(r.footer);
                    $('#'+table.id+"-operations").html(r.operations);
                    $('.table-checkbox').change(function(event){
                        if(event.target.checked)
                        {
                            wyf.tapi.checkedRows.push(event.target.value);
                        }
                        else
                        {
                            var index = wyf.tapi.checkedRows.indexOf(event.target.value);
                            wyf.tapi.checkedRows.splice(index, 1);
                        }
                        console.log(wyf.tapi.checkedRows);
                    }).each(function(){
                        if(wyf.tapi.checkedRows.indexOf(this.value) >= 0) this.checked = true;
                    });
                }
            });
        },
		
        sort:function(id,field)
        {
            if(wyf.tapi.tables[id].sort === "ASC")
            {
                wyf.tapi.tables[id].sort = "DESC";
            }
            else
            {
                wyf.tapi.tables[id].sort = "ASC";
            }
			
            //$("#"+id+">tbody").load(wyf.tapi.tables[id].path+"&sort="+field+"&sort_type="+wyf.tapi.tables[id].sort);
            wyf.tapi.tables[id].sort_field[0].field = field;
            wyf.tapi.tables[id].sort_field[0].type = wyf.tapi.tables[id].sort;
            wyf.tapi.render(wyf.tapi.tables[id]);
        },
		
        switchPage:function(id,page)
        {
            var table = wyf.tapi.tables[id]; 
            table.page = page;
            wyf.tapi.render(table);
            $("#"+id+"-page-id-"+page).addClass("page-selected");
            $("#"+id+"-page-id-"+table.prevPage).removeClass("page-selected");
            table.prevPage = page;
        },
		
        showSearchArea:function(id)
        {
            $("#tapi-"+id+"-search").slideToggle();
        },
		
        checkToggle:function(id,checkbox)
        {
            $("."+id+"-checkbox").prop('checked', checkbox.checked).each(function(){
                var index = wyf.tapi.checkedRows.indexOf(this.value);
                if(index >= 0) wyf.tapi.checkedRows.splice(index, 1);
                wyf.tapi.checkedRows.push(this.value);
            });
        },
		
        confirmBulkOperation:function(message, path)
        {
            var count = wyf.tapi.checkedRows.length;
            if(count === 0)
            {
                alert('Please select some items');
            }
            else
            {
                if(confirm(message + ' ' + count + ' selected item(s)'))
                {
                    document.location = path + '?ids=' + escape(JSON.stringify(wyf.tapi.checkedRows));
                }
            }
        },
		
        showOperations:function(tableId, id)
        {
            var offset = $('#'+tableId+'-operations-row-' + id).offset();
            var tableOffset = $('#' + tableId).offset();
            $(".operations-box").hide();
            
            $("#"+tableId+"-operations-box-" + id).css(
                {
                    left:((tableOffset.left) + $('#' + tableId).width() - ($("#"+tableId+"-operations-box-" + id).width() + 65))+'px',
                    top: (offset.top + 1) + 'px'
                }
            ).show();
			
        }
    },
    
    notifications : {
      show : function(param)
      {
        var notification;
        if(typeof param === 'string')
        {
          notification = {
            message: param,
            type: 'info',
            presentation: 'popup'
          }
        }
        else
        {
          notification = param;
        }
        
        console.log(notification);
        
        var object = document.createElement('div');
        object.innerHTML = notification.message;
        $(object).addClass('notification-' + notification.type).addClass(notification.presentation + '-notification');
        
        $('#' + notification.presentation + '-notifications').append(object);
        $(object).slideDown();
        setTimeout(
          function(){
            $(object).slideUp();
            $(object).remove();
          },
          5000
        );
      }
    }
};

function expand(id)
{
    $("#"+id).slideToggle("fast",
        function()
        {
            document.cookie = id+"="+$("#"+id).css("display");
            if(typeof menuExpanded === 'function')
            {
                menuExpanded();
            }
        }
    );
}
