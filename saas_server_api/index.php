<!DOCTYPE html>
<html class="js">
<head>
    <meta http-equiv="content-type" content="text/html; charset=windows-1252">
    <title>API TEST TOOL</title>
    <!--[if IE]>
    <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
    <script src="docs/js/jquery-1.10.2.min.js"></script>
    <script src="docs/js/jquery.form.js"></script>
    <script src="docs/js/jquery.blockUI.js"></script>
	<script src="docs/js/FlexiJsonEditor/json2.js"></script>
	<script src="docs/js/FlexiJsonEditor/jquery.jsoneditor.js"></script>
	<script src="docs/js/FlexiJsonEditor/jsoneditor.js"></script>
    <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
    <link href="docs/css/site.css" rel="stylesheet" type="text/css">
	<link rel="stylesheet" href="docs/js/FlexiJsonEditor/jsoneditor.css"/>
</head>
<body>
<div class="sidebar">
<?php include "docs/sidebar.php"?>
</div>
<div class="container">
<?php include "docs/method.php"?>
</div>
<script>
	// prepare the form when the DOM is ready
	$(document).ready(function() {
		var options = {
			//target:        '#output1',   // target element(s) to be updated with server response
			beforeSubmit:  showRequest,  // pre-submit callback
			success:       showResponse,  // post-submit callback

			// other available options:
			//url:       url         // override for form's 'action' attribute
			//type:      type        // 'get' or 'post', override for form's 'method' attribute
			dataType:  'json'        // 'xml', 'script', or 'json' (expected server response type)
			//clearForm: true        // clear all form fields after successful submit
			//resetForm: true        // reset the form after successful submit

			// $.ajax options can be used here too, for example:
			//timeout:   3000
		};

		// bind form using 'ajaxForm'
		$('#myForm1').ajaxForm(options);
	});

	// pre-submit callback
	function showRequest(formData, jqForm, options) {
		// formData is an array; here we use $.param to convert it to a string to display it
		// but the form plugin does this for you automatically when it submits the data
		var queryString = $.param(formData);
		$.blockUI({ message: '' });

		$('div.json-editor').block({
			message: '<span>Processing...</span>',
			css: { border: '3px solid #a00' }
		});
		// jqForm is a jQuery object encapsulating the form element.  To access the
		// DOM element for the form do this:
		// var formElement = jqForm[0];

		//alert('About to submit: \n\n' + queryString);

		// here we could return false to prevent the form from being submitted;
		// returning anything other than false will allow the form submit to continue
		return true;
	}

	// post-submit callback
	function showResponse(responseText, statusText, xhr, $form)  {
		// for normal html responses, the first argument to the success callback
		// is the XMLHttpRequest object's responseText property

		// if the ajaxForm method was passed an Options Object with the dataType
		// property set to 'xml' then the first argument to the success callback
		// is the XMLHttpRequest object's responseXML property

		// if the ajaxForm method was passed an Options Object with the dataType
		// property set to 'json' then the first argument to the success callback
		// is the json data object returned by the server
		$.unblockUI();
		var myjson = responseText;
		var opt = {
			change: function(data) { /* called on every change */ },
			propertyclick: function(path) { /* called when a property is clicked with the JS path to that property */ }
		};
		/* opt.propertyElement = '<textarea>'; */ // element of the property field, <input> is default
		/* opt.valueElement = '<textarea>'; */  // element of the value field, <input> is default
		$('#editor').jsonEditor(myjson, opt);
		$('div.json-editor').unblock();
		//alert('status: ' + statusText + '\n\nresponseText: \n' + responseText +
		//	'\n\nThe output div should have already been updated with the responseText.');
	}
</script>
</body>
</html>