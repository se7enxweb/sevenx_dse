'use strict';function
gid(id,context=null){return(context||document).getElementById(id);}function
qs(selector,context=null){return(context||document).querySelector(selector);}function
qsl(selector,context=null){const
els=qsa(selector,context);return els[els.length-1];}function
qsa(selector,context=null){return(context||document).querySelectorAll(selector);}function
partial(fn){const
args=Array.apply(null,arguments).slice(1);return function(){return fn.apply(this,args);};}function
partialArg(fn){const
args=Array.apply(null,arguments);return function(arg){args[0]=arg;return fn.apply(this,args);};}function
mixin(target,source){for(const
key
in
source){target[key]=source[key];}}function
toggle(id){gid(id).classList.toggle("hidden");return false;}function
cookie(assign,days){const
date=new
Date();date.setDate(date.getDate()+days);document.cookie=assign+'; expires='+date;}function
verifyVersion(baseUrl,token){document.addEventListener("DOMContentLoaded",()=>{cookie('neo_version=0',1);ajax('https://api.github.com/repos/adminneo-org/adminneo/releases/latest',(request)=>{const
response=JSON.parse(request.responseText);const
matches=response.tag_name.match(/^v(\d{1,2}\.\d{1,2}\.\d{1,2}(-(alpha|beta|rc)\d?)?)$/);if(!matches)return;const
version=matches[1];cookie('neo_version='+version,1);const
data='version='+version+'&token='+token;ajax(baseUrl+'script=version',null,data);},null,null,true);});}function
selectValue(select){if(!select.selectedIndex){return select.value;}const
selected=select.options[select.selectedIndex];return((selected.attributes.value||{}).specified?selected.value:selected.text);}function
isTag(el,tag){const
re=new
RegExp('^('+tag+')$','i');return el&&re.test(el.tagName);}function
parentTag(el,tag){while(el&&!isTag(el,tag)){el=el.parentNode;}return el;}function
trCheck(el){const
tr=parentTag(el,'tr');tr.classList.toggle('checked',el.checked);if(el.form&&el.form['all']&&el.form['all'].onclick){el.form['all'].onclick();}}function
selectCount(id,count){const
zero=count===0||count==='0'||count==='';setHtml(id,(zero?'':'('+(count+'').replace(/\B(?=(\d{3})+$)/g,thousandsSeparator)+')'));const
el=gid(id);if(!el)return;const
inputs=qsa('input[type="submit"]',el.parentNode.parentNode);for(let
input
of
inputs){input.disabled=zero;}}function
formCheck(name){for(const
el
of
this.form.elements){if(name.test(el.name)){el.checked=this.checked;trCheck(el);}}}function
tableCheck(){for(const
input
of
qsa('table.checkable td:first-child input')){trCheck(input);}}function
formUncheck(id){formUncheckAll("#"+id);}function
formUncheckAll(selector){for(const
element
of
qsa(selector)){element.checked=false;trCheck(element);}}function
formChecked(input,name){let
checked=0;for(const
el
of
input.form.elements){if(name.test(el.name)&&el.checked){checked++;}}return checked;}function
tableClick(event,click,canEdit=true){const
td=parentTag(event.target,'td');let
text;if(canEdit&&td&&(text=td.getAttribute('data-text'))){if(selectClick.call(td,event,+text,td.getAttribute('data-warning'))){return;}}click=(click||!window.getSelection||getSelection().isCollapsed);let
el=event.target;while(!isTag(el,'tr')){if(isTag(el,'table|a|input|textarea')){if(el.type!=='checkbox'){return;}checkboxClick.call(el,event);click=false;}el=el.parentNode;if(!el){return;}}el=el.firstChild.firstChild;if(click){el.checked=!el.checked;el.onclick&&el.onclick();}if(el.name==='check[]'){el.form['all'].checked=false;formUncheck('all-page');}if(/^(tables|views)\[\]$/.test(el.name)){formUncheck('check-all');}trCheck(el);}let
lastChecked;function
checkboxClick(event){if(!this.name){return;}if(event.shiftKey&&(!lastChecked||lastChecked.name===this.name)){const
checked=(lastChecked?lastChecked.checked:true);let
checking=!lastChecked;for(const
input
of
qsa('input',parentTag(this,'table'))){if(input.name===this.name){if(checking){input.checked=checked;trCheck(input);}if(input===this||input===lastChecked){if(checking){break;}checking=true;}}}}else{lastChecked=this;}}function
setHtml(id,html){const
el=qs('[id="'+id.replace(/[\\"]/g,'\\$&')+'"]');if(el){if(html==null){el.parentNode.innerHTML='';}else{el.innerHTML=html;}}}function
nodePosition(el){let
pos=0;while((el=el.previousSibling)){pos++;}return pos;}function
pageClick(href,page){if(!isNaN(page)&&page){location.href=href+(page!==1?'&page='+(page-1):'');}}function
initNavigation(){const
button=gid("navigation-button");const
panel=gid("navigation-panel");button.addEventListener("click",()=>{button.classList.toggle("opened");panel.classList.toggle("opened");});}let
tablesFilterTimeout=null;let
tablesFilterValue='';function
initTablesFilter(dbName){if(sessionStorage){document.addEventListener('DOMContentLoaded',()=>{if(dbName===sessionStorage.getItem('neo_tables_filter_db')&&sessionStorage.getItem('neo_tables_filter')){gid('tables-filter').value=sessionStorage.getItem('neo_tables_filter');filterTables();}else{sessionStorage.removeItem('neo_tables_filter');}sessionStorage.setItem('neo_tables_filter_db',dbName);});}const
filterInput=gid('tables-filter');filterInput.addEventListener('input',()=>{window.clearTimeout(tablesFilterTimeout);tablesFilterTimeout=window.setTimeout(filterTables,200);});document.body.addEventListener('keydown',event=>{if(isCtrl(event)&&event.shiftKey&&event.key.toUpperCase()==='F'){filterInput.focus();filterInput.select();event.preventDefault();}});}function
filterTables(){const
value=gid('tables-filter').value.toLowerCase();if(value===tablesFilterValue){return;}tablesFilterValue=value;let
reg
if(value!==''){const
valueExp=(`${value}`).replace(/[\\.+*?\[^\]$(){}=!<>|:]/g,'\\$&');reg=new
RegExp(`(${valueExp})`,'gi');}if(sessionStorage){sessionStorage.setItem('neo_tables_filter',value);}for(const
table
of
qsa('#tables li')){let
a=qs('*[data-primary="true"]',table);let
tableName=table.dataset.tableName;if(tableName==null){tableName=a.innerHTML.trim();table.dataset.tableName=tableName;}if(value===""){table.classList.remove('hidden');a.innerHTML=tableName;}else
if(tableName.toLowerCase().indexOf(value)>=0){table.classList.remove('hidden');a.innerHTML=tableName.replace(reg,'<strong>$1</strong>');}else{table.classList.add('hidden');}}}function
initFieldset(id){const
fieldset=gid(`fieldset-${id}`);fieldset.addEventListener("click",()=>{if(fieldset.classList.contains("closed")){fieldset.classList.remove("closed");}});qs("legend a",fieldset).addEventListener("click",event=>{fieldset.classList.toggle("closed");event.preventDefault();event.stopPropagation();});}function
initToggles(parent){for(const
link
of
qsa('.toggle',parent)){link.addEventListener("click",event=>{const
id=link.getAttribute('href').substring(1);gid(id).classList.toggle("hidden");link.classList.toggle("opened");event.preventDefault();event.stopPropagation();});}}function
initSettingsForm(){const
form=gid("settings");const
inputs=qsa("select, input[type='checkbox'], input[type='radio']",form);for(let
input
of
inputs){input.addEventListener("change",()=>{input.form.submit();});}}function
selectAddRow(event){const
field=this;const
row=cloneNode(field.parentNode);field.onchange=selectFieldChange;field.onchange(event);for(const
select
of
qsa('select',row)){select.name=select.name.replace(/[a-z]\[\d+/,'$&1');select.selectedIndex=0;}for(const
input
of
qsa('input',row)){input.name=input.name.replace(/[a-z]\[\d+/,'$&1');if(input.type==='checkbox'){input.checked=false;}else{input.value='';}}const
button=qs('.remove',row);button.onclick=selectRemoveRow;const
parent=field.parentNode.parentNode;if(parent.classList.contains("sortable")){initSortableRow(field.parentElement);}parent.appendChild(row);}function
selectRemoveRow(){this.parentElement.remove();return false;}function
selectSearchKeydown(event){if(event.keyCode===13||event.keyCode===10){this.onsearch=()=>{};}}(()=>{let
placeholderRow=null,nextRow=null,dragHelper=null;let
startScrollY,startY,minY,maxY,lastPointerY,rowHeight;window.initSortable=function(parentSelector){const
parent=qs(parentSelector);if(!parent)return;for(const
row
of
parent.children){if(!row.classList.contains("no-sort")){initSortableRow(row);}}};window.initSortableRow=function(row){row.classList.remove("no-sort");const
handle=qs(".handle",row);handle.addEventListener("mousedown",event=>{startSorting(row,event)});handle.addEventListener("touchstart",event=>{startSorting(row,event)});};window.isSorting=function(){return dragHelper!==null;};function
startSorting(row,event){event.preventDefault();const
pointerY=getPointerY(event);const
parent=row.parentNode;startScrollY=window.scrollY;startY=pointerY-getOffsetTop(row);minY=getOffsetTop(parent);maxY=minY+parent.offsetHeight-row.offsetHeight;placeholderRow=row.cloneNode(true);placeholderRow.classList.add("placeholder");parent.insertBefore(placeholderRow,row);rowHeight=placeholderRow.offsetHeight;if(row.tagName!=="TR"){rowHeight+=parseFloat(window.getComputedStyle(placeholderRow).marginBottom);}nextRow=row.nextElementSibling;let
top=pointerY-startY;let
left=getOffsetLeft(row);let
width=row.getBoundingClientRect().width;if(row.tagName==="TR"){const
firstChild=row.firstElementChild;const
borderWidth=(firstChild.offsetWidth-firstChild.clientWidth)/2;const
borderHeight=(firstChild.offsetHeight-firstChild.clientHeight)/2;minY-=borderHeight;maxY-=borderHeight;top-=borderHeight;left-=borderWidth;width+=2*borderWidth;for(const
child
of
row.children){child.style.width=child.getBoundingClientRect().width+"px";}dragHelper=document.createElement("table");dragHelper.appendChild(document.createElement("tbody")).appendChild(row);}else{dragHelper=row;}dragHelper.style.top=`${top}px`;dragHelper.style.left=`${left}px`;dragHelper.style.width=`${width}px`;dragHelper.classList.add("dragging");document.body.appendChild(dragHelper);window.addEventListener("mousemove",updateSorting);window.addEventListener("touchmove",updateSorting);window.addEventListener("scroll",updateSorting);window.addEventListener("mouseup",finishSorting);window.addEventListener("touchend",finishSorting);window.addEventListener("touchcancel",finishSorting);}function
updateSorting(event){const
pointerY=getPointerY(event);const
scrollingBoundary=30;const
speedCoefficient=8;let
distance=pointerY-scrollingBoundary;if(distance<0&&window.scrollY>0){window.scrollBy(0,distance/speedCoefficient);return;}distance=pointerY-window.innerHeight+scrollingBoundary;if(distance>0&&window.scrollY+window.innerHeight<document.documentElement.scrollHeight){window.scrollBy(0,distance/speedCoefficient);return;}let
top=Math.min(Math.max(pointerY-startY+window.scrollY-startScrollY,minY),maxY);dragHelper.style.top=`${top}px`;const
parent=placeholderRow.parentNode;let
oldNextRow=nextRow;top=top-minY+parent.offsetTop;let
testingRow=placeholderRow;do{if(top>testingRow.offsetTop+rowHeight/2+1){if(!nextRow.classList.contains("no-sort")){testingRow=nextRow;nextRow=nextRow.nextElementSibling;}else{break;}}else
if(top+rowHeight<testingRow.offsetTop+rowHeight/2-1){nextRow=testingRow=testingRow.previousElementSibling;}else{break;}}while(nextRow);if(nextRow!==oldNextRow){if(nextRow){parent.insertBefore(placeholderRow,nextRow);}else{parent.appendChild(placeholderRow);}}}function
finishSorting(){dragHelper.classList.remove("dragging");dragHelper.style.top=null;dragHelper.style.left=null;dragHelper.style.width=null;document.body.removeChild(dragHelper);placeholderRow.parentNode.insertBefore(dragHelper.tagName==="TABLE"?dragHelper.firstChild.firstChild:dragHelper,placeholderRow);placeholderRow.remove();placeholderRow=nextRow=dragHelper=null;window.removeEventListener("mousemove",updateSorting);window.removeEventListener("touchmove",updateSorting);window.removeEventListener("scroll",updateSorting);window.removeEventListener("mouseup",finishSorting);window.removeEventListener("touchend",finishSorting);window.removeEventListener("touchcancel",finishSorting);}function
getPointerY(event){if(event.type.includes("touch")){const
touch=event.touches[0]||event.changedTouches[0];lastPointerY=touch.clientY;}else
if(event.clientY!==undefined){lastPointerY=event.clientY;}return lastPointerY;}})();function
columnMouse(className){for(const
span
of
qsa('span',this)){if(/column/.test(span.className)){span.className='column'+(className||'');}}}function
selectSearch(name){const
el=gid('fieldset-search');el.className='';const
divs=qsa('div',el);let
i,div;for(i=0;i<divs.length;i++){div=divs[i];const
el=qs('[name$="[col]"]',div);if(el&&selectValue(el)===name){break;}}if(i===divs.length){div.firstChild.value=name;div.firstChild.onchange();}qs('[name$="[val]"]',div).focus();return false;}function
isCtrl(event){return(event.ctrlKey||event.metaKey)&&!event.altKey;}function
bodyKeydown(event,button){eventStop(event);let
target=event.target;if(target.jushTextarea){target=target.jushTextarea;}if(isCtrl(event)&&(event.keyCode===13||event.keyCode===10)&&isTag(target,'select|textarea|input')){target.blur();if(target.form[button]){target.form[button].click();}else{target.form.dispatchEvent(new
Event('submit',{bubbles:true}));target.form.submit();}target.focus();return false;}return true;}function
bodyClick(event){const
target=event.target;if((isCtrl(event)||event.shiftKey)&&target.type==='submit'&&isTag(target,'input')){target.form.target='_blank';setTimeout(()=>{target.form.target='';},0);}}function
onEditingKeydown(event){if((event.keyCode===40||event.keyCode===38)&&isCtrl(event)){event.preventDefault();const
target=event.target;let
row=parentTag(target,"tr");if(!row){return false;}row=event.keyCode===40?row.nextElementSibling:row.previousElementSibling;if(!row||!isTag(row,'tr')){return false;}const
cell=row.childNodes[nodePosition(parentTag(target,"th|td"))];if(!cell){return false;}let
input=cell.childNodes[nodePosition(target)];if(!input||!isTag(input,"input|select|textarea|pre|button")||input.classList.contains("hidden")){input=qs("input:not(.hidden), select:not(.hidden), textarea:not(.hidden), pre.jush, button",cell);}if(input){input.focus();}return false;}if(event.shiftKey&&!bodyKeydown(event,'insert')){event.preventDefault();return false;}return true;}function
functionChange(){const
func=selectValue(this);const
inputName=this.name.replace(/^function/,'fields');const
input=this.form[inputName]||this.form[`${inputName}[]`];if(func&&func!=="NULL"&&input.type!=="file"){if(input.origType===undefined){input.origType=input.type;input.origMaxLength=input.dataset.maxlength;}input.removeAttribute('data-maxlength');input.type='text';}else
if(input.origType){input.type=input.origType;if(input.origMaxLength>=0){input.setAttribute('data-maxlength',input.origMaxLength);}}if(func==="NULL"||func==="now"){if(input.type==="select-one"){input.lastValue=input.value;input.value="__adminneo_empty__";}else
if(input.length){let
checkedList=[];for(let
i=0;i<input.length;i++){const
radio=input[i];if(!radio.checked)continue;checkedList.push(i);radio.checked=false;if(radio.type==="radio"){break;}}input.lastValue=checkedList;}else{input.lastValue=input.value;input.value="";}}else
if(input.lastValue){if(input.type!=="select-one"&&input.length){for(let
index
of
input.lastValue){input[index].checked=true;}}else{input.value=input.lastValue;}}else{if(input.type==="select-one"){if(input.options[0].value==="__adminneo_empty__"){input.value=input.options[1].value;}}else
if(input.length&&input[0].type==="radio"){input[0].checked=true;}}if(!input.length){oninput({target:input});}}function
skipOriginal(first){const
fnSelect=qs('select',this.previousSibling);const
value=selectValue(fnSelect);if(fnSelect.selectedIndex<first||value==="NULL"||value==="now"){fnSelect.selectedIndex=first;}}function
fieldChange(){const
row=cloneNode(parentTag(this,'tr'));for(const
input
of
qsa('input',row)){input.value='';}parentTag(this,'table').appendChild(row);this.oninput=()=>{};}function
ajax(url,onSuccess=null,data=null,progressMessage=null,failSilently=false){const
ajaxStatus=gid('ajaxstatus');if(progressMessage){ajaxStatus.innerHTML='<div class="message">'+progressMessage+'</div>';ajaxStatus.classList.remove("hidden");}else{ajaxStatus.classList.add("hidden");}const
request=new
XMLHttpRequest();request.open((data?'POST':'GET'),url);request.setRequestHeader('X-Requested-With','XMLHttpRequest');if(data){request.setRequestHeader('Content-Type','application/x-www-form-urlencoded');}request.onreadystatechange=()=>{if(request.readyState===4){if(request.status>=200&&request.status<300){if(onSuccess){onSuccess(request);}}else
if(failSilently){console.error(request.status?request.responseText:"No internet connection");}else{ajaxStatus.innerHTML=(request.status?request.responseText:'<div class="error">'+offlineMessage+'</div>');ajaxStatus.classList.remove("hidden");}}};request.send(data);return request;}function
ajaxSetHtml(url){return!ajax(url,request=>{const
data=window.JSON?JSON.parse(request.responseText):eval('('+request.responseText+')');for(const
key
in
data){setHtml(key,data[key]);}});}function
ajaxForm(form,message,button){let
data=[];for(const
el
of
form.elements){if(el.name&&!el.disabled){if(/^file$/i.test(el.type)&&el.value){return false;}if(!/^(checkbox|radio|submit|file)$/i.test(el.type)||el.checked||el===button){data.push(encodeURIComponent(el.name)+'='+encodeURIComponent(isTag(el,'select')?selectValue(el):el.value));}}}data=data.join('&');let
url=form.action;if(!/post/i.test(form.method)){url=url.replace(/\?.*/,'')+'?'+data;data='';}return ajax(url,request=>{setHtml('ajaxstatus',request.responseText);if(window.jush){jush.highlight_tag(qsa('code',gid('ajaxstatus')),0);}initToggles(gid('ajaxstatus'));},data,message);}function
initTableFooter(){const
footer=qs(".table-footer");if(!footer)return;const
options={root:qs(".table-footer-parent"),rootMargin:"0px 0px -1px 0px",threshold:1.0,};const
observer=new
IntersectionObserver((entries)=>{const
entry=entries[0];footer.classList.toggle("sticky",entry.boundingClientRect.bottom<entry.rootBounds.bottom);},options);observer.observe(footer);}function
selectClick(event,text,warning){const
td=this;const
target=event.target;if(!isCtrl(event)||td.dataset.editing||(!event.shiftKey&&parentTag(target,'a'))){return false;}event.preventDefault();if(warning){alert(warning);return true;}const
original=td.innerHTML;text=text||/\n/.test(original);const
input=document.createElement(text?'textarea':'input');if(!text){input.classList.add("input");}input.onkeydown=event=>{if(event.keyCode===27&&!event.shiftKey&&!event.altKey&&!isCtrl(event)){td.dataset.editing="";td.innerHTML=original;initToggles(td);}};const
dataset=td.firstChild?(td.firstChild.dataset||{}):{};let
value;if(dataset.value!==undefined){const
dom=new
DOMParser().parseFromString(dataset.value,"text/html");value=dom.documentElement.innerText;}else{value=td.innerText;}const
tdStyle=window.getComputedStyle(td);input.style.width=Math.max(td.clientWidth-parseFloat(tdStyle.paddingLeft)-parseFloat(tdStyle.paddingRight),(text?200:20))+'px';if(text){let
rows=1;value.replace(/\n/g,()=>{rows++;});input.rows=rows;}if(qsa('i',td).length){value='';}const
pos=event.rangeOffset!==undefined?event.rangeOffset:getSelection().anchorOffset;td.dataset.editing="true";td.innerHTML='';td.appendChild(input);input.focus();if(text===2){return ajax(location.href+'&'+encodeURIComponent(td.id)+'=',request=>{if(request.responseText){input.value=request.responseText;input.name=td.id;}});}input.value=value;input.name=td.id;input.selectionStart=pos;input.selectionEnd=pos;return true;}function
loadNextPage(limit,loadingText){const
a=this;const
title=a.innerHTML;const
href=a.href;if(!href){return true;}a.innerHTML=loadingText;a.removeAttribute('href');return!ajax(href,request=>{const
newBody=document.createElement('tbody');newBody.innerHTML=request.responseText;jush.highlight_tag(qsa("code",newBody),0);initToggles(newBody);const
lastPage=newBody.children.length<limit;const
tableBody=qs('#table tbody');while(newBody.children.length){tableBody.appendChild(newBody.children[0]);}if(lastPage){a.parentElement.remove();}else{a.href=href.replace(/\d+$/,page=>+page+1);a.innerHTML=title;}});}function
eventStop(event){if(event.stopPropagation){event.stopPropagation();}else{event.cancelBubble=true;}}function
cloneNode(el){const
el2=el.cloneNode(true);const
selector='input, select';const
origEls=qsa(selector,el);const
cloneEls=qsa(selector,el2);for(let
i=0;i<origEls.length;i++){const
origEl=origEls[i];for(const
key
in
origEl){if(/^on/.test(key)&&origEl[key]){cloneEls[i][key]=origEl[key];}}}return el2;}function
getOffsetTop(element){let
box=element.getBoundingClientRect();return box.top+window.scrollY;}function
getOffsetLeft(element){let
box=element.getBoundingClientRect();return box.left+window.scrollX;}oninput=event=>{const
target=event.target;const
maxLength=target.getAttribute('data-maxlength');target.classList.toggle('maxlength',target.value&&maxLength!=null&&target.value.length>maxLength);};function
initSyntaxHighlighting(version,vendor,autocompletion){if(!window.jush){return;}jush.create_links=' target="_blank" rel="noreferrer noopener"';if(version){for(let
key
in
jush.urls){let
obj=jush.urls;if(typeof
obj[key]!='string'){obj=obj[key];key=0;if(vendor==='mariadb'){for(let
i=1;i<obj.length;i++){obj[i]=obj[i].replace('.html','/').replace('-type-syntax','-data-types').replace(/numeric-(data-types)/,'$1-$&').replace(/replication-options-(master|binary-log)\//,'replication-and-binary-log-system-variables/').replace('server-options/','server-system-variables/').replace('innodb-parameters/','innodb-system-variables/').replace(/#(statvar|sysvar|option_mysqld)_(.*)/,'#$2').replace(/#sysvar_(.*)/,'#$1');}}}obj[key]=(vendor==="mariadb"?obj[key].replace('dev.mysql.com/doc/mysql','mariadb.com/kb'):obj[key]).replace('/doc/mysql','/doc/refman/'+version);if(vendor!=='cockroach'){obj[key]=obj[key].replace('/docs/current','/docs/'+version);}}}if(window.jushLinks){jush.custom_links=jushLinks;}jush.highlight_tag('code',0);for(const
textarea
of
qsa('textarea')){if(textarea.className.match(/(^|\s)jush-/)){const
pre=jush.textarea(textarea,autocompletion,{silentStart:true});if(pre){textarea.onchange=()=>{pre.textContent=textarea.value;pre.oninput();};}}}}function
typePassword(el,disable){try{el.type=(disable?'text':'password');}catch(e){}}function
initLoginDriver(driverSelect){const
table=parentTag(driverSelect,'table');let
serverLabelDefault=null;let
serverPlaceholderDefault=null;driverSelect.onchange=()=>{const
isSqlite=/sqlite/.test(selectValue(driverSelect));['username'].forEach(field=>{const
tr=table.querySelector('tr[data-field="'+field+'"]');if(!tr)return;tr.style.display=isSqlite?'none':'';const
input=tr.querySelector('input');if(input)input.disabled=isSqlite;});const
serverTr=table.querySelector('tr[data-field="server"]');const
serverTh=serverTr?serverTr.querySelector('th'):null;const
serverInput=serverTr?serverTr.querySelector('input'):null;if(serverTr){serverTr.style.display='';}if(serverInput){serverInput.disabled=false;}if(serverTh&&serverLabelDefault===null)serverLabelDefault=serverTh.textContent;if(serverInput&&serverPlaceholderDefault===null)serverPlaceholderDefault=serverInput.placeholder;if(serverTh)serverTh.textContent=isSqlite?'File':(serverLabelDefault||'');if(serverInput)serverInput.placeholder=isSqlite?'var/storage/sqlite3/test.db':(serverPlaceholderDefault||'');const
dbTr=table.querySelector('tr[data-field="db"]');if(dbTr){dbTr.style.display=isSqlite?'none':'';const
dbInput=dbTr.querySelector('input');if(dbInput)dbInput.disabled=isSqlite;}};driverSelect.onchange();}document.addEventListener('DOMContentLoaded',()=>{const
driverSelect=document.querySelector('select[name="auth[driver]"]');if(driverSelect){initLoginDriver(driverSelect);}});let
dbCtrl;const
dbPrevious={};function
dbMouseDown(event){if(event.target.tagName==="OPTION")return;dbCtrl=isCtrl(event);if(dbPrevious[this.name]===undefined){dbPrevious[this.name]=this.value;}}function
dbChange(){if(dbCtrl){this.form.target='_blank';}this.form.submit();this.form.target='';if(dbCtrl&&dbPrevious[this.name]!==undefined){this.value=dbPrevious[this.name];dbPrevious[this.name]=undefined;}}function
selectFieldChange(){const
form=this.form;const
ok=(()=>{for(const
input
of
qsa('input',form)){if(input.value&&/^fulltext/.test(input.name)){return true;}}let
ok=form.limit.value;let
group=false;const
columns={};for(const
select
of
qsa('select',form)){const
col=selectValue(select);let
match=/^(where.+)col]/.exec(select.name);if(match){const
op=selectValue(form[match[1]+'op]']);const
val=form[match[1]+'val]'].value;if(col
in
indexColumns&&(!/LIKE|REGEXP/.test(op)||(op==='LIKE'&&val.charAt(0)!=='%'))){return true;}else
if(col||val){ok=false;}}if((match=/^(columns.+)fun]/.exec(select.name))){if(/^(avg|count|count distinct|group_concat|max|min|sum)$/.test(col)){group=true;}const
val=selectValue(form[match[1]+'col]']);if(val){columns[col&&col!=='count'?'':val]=1;}}if(col&&/^order/.test(select.name)){if(!(col
in
indexColumns)){ok=false;}break;}}if(group){for(const
column
in
columns){if(!(column
in
indexColumns)){ok=false;}}}return ok;})();setHtml('noindex',(ok?'':'!'));}(()=>{let
added='.';let
lastType='';window.initFieldsEditing=function(table){const
tableBody=qs("tbody",table);tableBody.addEventListener("keydown",onEditingKeydown);const
rows=qsa("tr",tableBody);for(let
row
of
rows){initFieldsEditingRow(row);}};function
initFieldsEditingRow(row,autoAddRow=true){let
field=qs('[name$="[field]"]',row);if(field){field.addEventListener("input",event=>{const
input=event.target;detectForeignKey(input);if(autoAddRow&&!input.defaultValue){addRow(input);autoAddRow=false;}});}field=qs('[name$="[type]"]',row);field.addEventListener("focus",event=>{lastType=selectValue(event.target);});field.addEventListener("change",onFieldTypeChange);initHelpFor(field,(value)=>{return value;},true);field=qs('[name$="[length]"]',row);field.addEventListener("focus",onFieldLengthFocus);field.addEventListener("input",event=>{const
input=event.target;input.classList.toggle('required',!input.value.length&&/var(char|binary)$/.test(selectValue(input.parentNode.previousSibling.firstChild)));});field=qs("[name='auto_increment_col']",row);if(field){field.addEventListener("click",event=>{const
input=event.target;const
field=input.form['fields['+input.value+'][field]'];if(!field.value){field.value="id";field.dispatchEvent(new
Event("input"));}});}field=qs('[name$="[default]"]',row);if(field){field.addEventListener("input",event=>{const
element=event.target.previousElementSibling;element.checked=true;if(!element.selectedIndex){element.selectedIndex=1;}});}let
button=qs("button[name^='add']",row);if(button){button.addEventListener("click",event=>{addRow(event.currentTarget,true);event.preventDefault();});}button=qs("button[name^='drop_col']",row);if(button){button.addEventListener("click",event=>{removeTableRow(event.currentTarget,"field");event.preventDefault();});}}function
detectForeignKey(input){const
name=input.name.substring(0,input.name.length-7);const
typeSelect=input.form.elements[name+'[type]'];const
options=typeSelect.options;const
value=input.value;let
candidate;for(let
i=options.length;i--;){const
match=/(.+)`(.+)/.exec(options[i].value);if(!match){if(candidate&&i===options.length-2&&value===options[candidate].value.replace(/.+`/,'')&&name==='fields[1]'){return;}break;}let
table=match[1];const
column=match[2];const
tables=[table,table.replace(/s$/,''),table.replace(/es$/,'')];for(const
table
of
tables){if(value===column||value===table||delimiterEqual(value,table,column)||delimiterEqual(value,column,table)){if(candidate){return;}candidate=i;break;}}}if(candidate){typeSelect.selectedIndex=candidate;typeSelect.dispatchEvent(new
Event('change'));}}function
delimiterEqual(value,part1,part2){return(value===part1+'_'+part2||value===part1+part2||value===part1+part2.charAt(0).toUpperCase()+part2.substring(1));}function
onFieldLengthFocus(){const
td=this.parentNode;if(/(enum|set)$/.test(selectValue(td.previousSibling.firstChild))){const
edit=gid('enum-edit');edit.value=parseEnumValues(this.value);td.appendChild(edit);this.style.display='none';edit.style.display='inline';edit.focus();}}window.onFieldLengthBlur=function(){const
field=this.parentNode.firstChild;const
value=this.value;field.value=(/^'[^\n]+'$/.test(value)?value:value&&"'"+value.replace(/\n+$/,'').replace(/'/g,"''").replace(/\\/g,'\\\\').replace(/\n/g,"','")+"'");field.style.display='inline';this.style.display='none';};function
parseEnumValues(string){const
re=/(^|,)\s*'(([^\\']|\\.|'')*)'\s*/g;const
result=[];let
offset=0;let
match;while((match=re.exec(string))){if(offset!==match.index){break;}result.push(match[2].replace(/'(')|\\(.)/g,'$1$2'));offset+=match[0].length;}return offset===string.length?result.join('\n'):string;}function
onFieldTypeChange(){const
type=this;const
name=type.name.substring(0,type.name.length-6);const
text=selectValue(type);for(const
el
of
type.form.elements){if(el.name===name+'[length]'){if(!((/(char|binary)$/.test(lastType)&&/(char|binary)$/.test(text))||(/(enum|set)$/.test(lastType)&&/(enum|set)$/.test(text)))){el.value='';}el.dispatchEvent(new
Event("input"));}if(lastType==='timestamp'&&el.name===name+'[generated]'&&/timestamp/i.test(type.form.elements[name+'[default]'].value)){el.checked=false;el.selectedIndex=0;}if(el.name===name+'[collation]'){el.classList.toggle('hidden',!/(char|text|enum|set)$/.test(text));}if(el.name===name+'[unsigned]'){el.classList.toggle('hidden',!/(^|[^o])int(?!er)|numeric|real|float|double|decimal|money/.test(text));}if(el.name===name+'[on_update]'){el.classList.toggle('hidden',!/timestamp|datetime/.test(text));}if(el.name===name+'[on_delete]'){el.classList.toggle('hidden',!/`/.test(text));}}}function
addRow(button,focus=false){const
match=/(\d+)(\.\d+)?/.exec(button.name);const
newIndex=match[0]+(match[2]?added.substring(match[2].length):added)+'1';const
row=parentTag(button,'tr');const
newRow=cloneNode(row);let
inputs=qsa('select, input, button',row);let
newInputs=qsa('select, input, button',newRow);for(let
i=0;i<inputs.length;i++){newInputs[i].name=inputs[i].name.replace(/[0-9.]+/,newIndex);if(newInputs[i].tagName==="SELECT"){newInputs[i].selectedIndex=/\[(generated)/.test(inputs[i].name)?0:inputs[i].selectedIndex;}}inputs=qsa('input',row);newInputs=qsa('input',newRow);for(let
i=0;i<inputs.length;i++){if(inputs[i].name==='auto_increment_col'){newInputs[i].value=newIndex;newInputs[i].checked=false;}if(/\[(orig|field|comment|default)/.test(inputs[i].name)){newInputs[i].value='';}if(/\[(generated)/.test(inputs[i].name)){newInputs[i].checked=false;}}initFieldsEditingRow(newRow,!focus);const
parent=parentTag(button,"tbody");if(parent.classList.contains("sortable")){initSortableRow(newRow);}row.parentNode.insertBefore(newRow,row.nextSibling);if(focus){newInputs[0].focus();}added+='0';}})();function
onRemoveIndexRowClick(){removeTableRow(this,"type");return false;}function
removeTableRow(button,columnName){const
row=parentTag(button,"tr");const
input=qs(`[name$='[${columnName}]']`,row);input.remove();row.style.display='none';return false;}function
columnShow(checked,column){for(const
tr
of
qsa('tr',gid('edit-fields'))){qsa('td',tr)[column].classList.toggle('hidden',!checked);}}function
indexOptionsShow(checked){for(const
option
of
qsa(".idxopts")){option.classList.toggle("hidden",!checked);}}function
partitionByChange(){const
partitionTable=/RANGE|LIST/.test(selectValue(this));this.form['partitions'].classList.toggle('hidden',partitionTable||!this.selectedIndex);gid('partition-table').classList.toggle('hidden',!partitionTable);}function
partitionNameChange(){const
row=cloneNode(parentTag(this,'tr'));row.firstChild.firstChild.value='';parentTag(this,'table').appendChild(row);this.oninput=()=>{};}function
editingCommentsClick(el,columnIndex){const
comment=el.form['Comment'];columnShow(el.checked,columnIndex);comment.classList.toggle('hidden',!el.checked);if(el.checked){comment.focus();}}function
dumpClick(event){let
el=parentTag(event.target,'label');if(!el)return;el=qs('input',el);const
match=/(.+)\[]$/.exec(el.name);if(match){checkboxClick.call(el,event);formUncheck('check-'+match[1]);}}function
foreignAddRow(){const
row=cloneNode(parentTag(this,'tr'));this.onchange=()=>{};for(const
select
of
qsa('select',row)){select.name=select.name.replace(/\d+]/,'1$&');select.selectedIndex=0;}parentTag(this,'table').appendChild(row);}function
indexesAddRow(){const
row=cloneNode(parentTag(this,'tr'));this.onchange=()=>{};for(const
select
of
qsa('select',row)){select.name=select.name.replace(/indexes\[\d+/,'$&1');select.selectedIndex=0;}for(const
input
of
qsa('input',row)){input.name=input.name.replace(/indexes\[\d+/,'$&1');input.value='';}parentTag(this,'table').appendChild(row);}function
indexesChangeColumn(prefix){const
names=[];for(const
tag
in{'select':1,'input':1}){for(const
column
of
qsa(tag,parentTag(this,'td'))){if(/\[columns]/.test(column.name)){const
value=selectValue(column);if(value){names.push(value);}}}}this.form[this.name.replace(/].*/,'][name]')].value=prefix+names.join('_');}function
indexesAddColumn(prefix){const
field=this;const
select=field.form[field.name.replace(/].*/,'][type]')];if(!select.selectedIndex){while(selectValue(select)!=="INDEX"&&select.selectedIndex<select.options.length){select.selectedIndex++;}select.onchange();}const
column=cloneNode(field.parentNode);for(const
select
of
qsa('select',column)){select.name=select.name.replace(/]\[\d+/,'$&1');select.selectedIndex=0;}field.onchange=partial(indexesChangeColumn,prefix);for(const
input
of
qsa('input',column)){input.name=input.name.replace(/]\[\d+/,'$&1');if(input.type!=='checkbox'){input.value='';}}parentTag(field,'td').appendChild(column);field.onchange();}function
sqlSubmit(form,root){if(encodeURIComponent(form['query'].value).length<500){form.action=root+'&sql='+encodeURIComponent(form['query'].value)+(form['limit'].value?'&limit='+
+form['limit'].value:'')+(form['error_stops'].checked?'&error_stops=1':'')+(form['only_errors'].checked?'&only_errors=1':'');}}function
triggerChange(tableRe,table,form){const
formEvent=selectValue(form['Event']);if(tableRe.test(form['Trigger'].value)){form['Trigger'].value=table+'_'+(selectValue(form['Timing']).charAt(0)+formEvent.charAt(0)).toLowerCase();}form['Of'].classList.toggle('hidden',!/ OF/.test(formEvent));}let
that,x,y;function
schemaMousedown(event){if((event.which?event.which:event.button)===1){that=this;x=event.clientX-this.offsetLeft;y=event.clientY-this.offsetTop;}}function
schemaMousemove(event){if(that!==undefined){const
left=(event.clientX-x)/em;const
top=(event.clientY-y)/em;const
lineSet={};for(const
div
of
qsa('div',that)){if(div.classList.contains('references')){const
div2=qs('[id="'+(/^refs/.test(div.id)?'refd':'refs')+div.id.substr(4)+'"]');const
ref=(tablePos[div.title]?tablePos[div.title]:[div2.parentNode.offsetTop/em,0]);let
left1=-1;const
id=div.id.replace(/^ref.(.+)-.+/,'$1');if(div.parentNode!==div2.parentNode){left1=Math.min(0,ref[1]-left)-1;div.style.left=left1+'em';div.querySelector('div').style.width=-left1+'em';const
left2=Math.min(0,left-ref[1])-1;div2.style.left=left2+'em';div2.querySelector('div').style.width=-left2+'em';}if(!lineSet[id]){const
line=qs('[id="'+div.id.replace(/^....(.+)-.+$/,'refl$1')+'"]');const
top1=top+div.offsetTop/em;let
top2=top+div2.offsetTop/em;if(div.parentNode!==div2.parentNode){top2+=ref[0]-top;line.querySelector('div').style.height=Math.abs(top1-top2)+'em';}line.style.left=(left+left1)+'em';line.style.top=Math.min(top1,top2)+'em';lineSet[id]=true;}}}that.style.left=left+'em';that.style.top=top+'em';}}function
schemaMouseup(event,db){if(that!==undefined){tablePos[that.firstChild.firstChild.firstChild.data]=[(event.clientY-y)/em,(event.clientX-x)/em];that=undefined;let
s='';for(const
key
in
tablePos){s+='_'+key+':'+Math.round(tablePos[key][0])+'x'+Math.round(tablePos[key][1]);}s=encodeURIComponent(s.substr(1));const
link=gid('schema-link');link.href=link.href.replace(/[^=]+$/,'')+s;cookie('neo_schema-'+db+'='+s,30);}}(()=>{let
openTimeout=null;let
closeTimeout=null;let
helpVisible=false;window.initHelpPopup=function(){const
help=gid("help");help.addEventListener("mouseenter",()=>{clearTimeout(closeTimeout);closeTimeout=null;});help.addEventListener("mouseleave",hideHelp);};window.initHelpFor=function(element,content,side=false){const
withCallback=typeof
content==="function";element.addEventListener("mouseenter",event=>{showHelp(event.target,withCallback?content(event.target.value):content,side)});element.addEventListener("mouseleave",hideHelp);element.addEventListener("blur",hideHelp);if(withCallback){element.addEventListener("change",hideHelp);}};function
showHelp(element,text,side){if(!text){hideHelp();return;}if(isSorting()||!window.jush){return;}clearTimeout(openTimeout);openTimeout=null;clearTimeout(closeTimeout);closeTimeout=null;const
help=gid("help");help.innerHTML=text;jush.highlight_tag([help]);help.classList.remove("hidden");const
rect=element.getBoundingClientRect();const
root=document.documentElement;let
top=root.scrollTop+rect.top;let
left=root.scrollLeft+rect.left;if(side){left-=help.offsetWidth;if(left<0){left=rect.left;top-=help.offsetHeight;}else{top-=(help.offsetHeight-element.offsetHeight)/2;}}else{top-=help.offsetHeight;left-=(help.offsetWidth-element.offsetWidth)/2;}help.style.top=`${top}px`;help.style.left=`${left}px`;if(helpVisible){return;}help.classList.add("hidden");openTimeout=setTimeout(()=>{gid("help").classList.remove("hidden");helpVisible=true;openTimeout=null;},600);}function
hideHelp(){if(openTimeout){clearTimeout(openTimeout);openTimeout=null;return;}closeTimeout=setTimeout(()=>{gid("help").classList.add("hidden");helpVisible=false;closeTimeout=null;},200);}})();