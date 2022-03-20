YUI.add("moodle-availability_quizquestion-form",function(q,e){M.availability_quizquestion=M.availability_quizquestion||{},M.availability_quizquestion.form=q.Object(M.core_availability.plugin),M.availability_quizquestion.form.quizzes=null,M.availability_quizquestion.form.states=null,M.availability_quizquestion.form.initInner=function(e,i){this.quizzes=e,this.states=i},M.availability_quizquestion.form.getNode=function(e){var i,t,a,s,l='<span class="availability-group">';for(l+='<label><span class="p-r-1">'+M.util.get_string("title","availability_quizquestion")+'</span> <select name="quizid" class="custom-select"><option value="">'+M.util.get_string("choosedots","moodle")+"</option>",i=0;i<this.quizzes.length;i++)l+='<option value="'+this.quizzes[i].id+'">'+this.quizzes[i].name+"</option>";for(l=(l=(l+="</select></label>")+(' <label><span class="sr-only">'+M.util.get_string("label_question","availability_quizquestion")+'</span><select name="questionid" class="custom-select"><option value="">'+M.util.get_string("choosedots","moodle")+"</option>")+"</select></label>")+(' <label><span class="sr-only">'+M.util.get_string("label_state","availability_quizquestion")+'</span><select name="requiredstate" class="custom-select"><option value="">'+M.util.get_string("choosedots","moodle")+"</option>"),i=0;i<this.states.length;i++)l+='<option value="'+this.states[i].shortname+'">'+this.states[i].displayname+"</option>";return t=q.Node.create('<span class="form-inline">'+(l=l+"</select></label>"+"</span>")+"</span>"),a=function(l,n,o){var u,e=l.get("value"),t=M.cfg.wwwroot+"/availability/condition/quizquestion/ajax.php?quizid="+e;n.all("option").each(function(e){""!==e.get("value")&&e.remove()},this),e&&(l.set("disabled",!0),u={},M.util.js_pending(u),q.io(t,{on:{success:function(e,i){for(var t,a=q.JSON.parse(i.responseText),s=0;s<a.length;s++)(t=document.createElement("option")).value=a[s].id,t.innerHTML=a[s].name,n.append(t);l.set("disabled",!1),o!==undefined&&o(),M.core_availability.form.update(),M.util.js_complete(u)},failure:function(e,i){l.set("disabled",!1),M.util.js_complete(u);i=i.statusText;M.cfg.developerdebug&&(i+=" ("+t+")"),new M.core.exception({message:i})}}}))},e.quizid!==undefined&&t.one("select[name=quizid] > option[value="+e.quizid+"]")&&(t.one("select[name=quizid]").set("value",""+e.quizid),a(t.one("select[name=quizid]"),t.one("select[name=questionid]"),function(){e.questionid!==undefined&&t.one("select[name=questionid] > option[value="+e.questionid+"]")&&t.one("select[name=questionid]").set("value",""+e.questionid)})),e.requiredstate!==undefined&&t.one("select[name=requiredstate] > option[value="+e.requiredstate+"]")&&t.one("select[name=requiredstate]").set("value",""+e.requiredstate),M.availability_quizquestion.form.addedEvents||(M.availability_quizquestion.form.addedEvents=!0,(s=q.one(".availability-field")).delegate("change",function(){M.core_availability.form.update()},".availability_quizquestion select"),s.delegate("change",function(){var e=this.ancestor("span.availability_quizquestion"),i=e.one("select[name=quizid]"),e=e.one("select[name=questionid]");a(i,e)},".availability_quizquestion select[name=quizid]")),t},M.availability_quizquestion.form.fillValue=function(e,i){var t=i.one("select[name=quizid]").get("value"),a=i.one("select[name=questionid]").get("value"),i=i.one("select[name=requiredstate]").get("value");e.quizid=""===t?"":parseInt(t,10),e.questionid=""===a?"":parseInt(a,10),e.requiredstate=i},M.availability_quizquestion.form.fillErrors=function(e,i){var t={};this.fillValue(t,i),""===t.quizid&&e.push("availability_quizquestion:error_selectquiz"),""===t.questionid&&e.push("availability_quizquestion:error_selectquestion"),""===t.requiredstate&&e.push("availability_quizquestion:error_selectstate")}},"@VERSION@",{requires:["base","node","event","moodle-core_availability-form"]});