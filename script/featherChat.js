$(document).ready(function(){

	$("#username").select();
	
	//create a new WebSocket object.	
	var wsUri = serverAddr;
	websocket = new WebSocket(wsUri); 

	$("#message").keypress(function(e) {
	    if(e.which == 13) {
			var mymessage = $('#message').val(); //get message text			
			if(mymessage != ""){
				//prepare json data
				var msg = {
					type: 'message',
					message: mymessage
				};
				//convert and send data to server
				websocket.send(JSON.stringify(msg));
				$('#message').val(''); //reset text
			}
	    }
	});
		
	//#### Message received from server?
	websocket.onmessage = function(ev) {
		var msg = JSON.parse(ev.data); //PHP sends Json data
		
		$("#clientList").empty();
		for (var i = 0; i < msg.clients.length; i++) {
			$("#clientList").append("<div class='user_name'>"+msg.clients[i]+"</div>");
		}
		
		if(msg.type == 'loginOk'){
			$("#nameInput").slideUp(400,function(){$("#chat_wrapper").slideDown(400,function(){$("#message").focus();})});
		}
		if(msg.type == 'loginNok'){
			$("#username").parent().attr("data-tooltip-text",msg.message);
			$("#username").parent().attr("data-tooltip-placement","left");
			$("#username").parent().attr("data-tooltip-type","error");
			$("#username").parent().addClass("error");
			$("#username").val("");
		}
		if(msg.type == 'usermsg')
			userMessage(msg);			
			
		if(msg.type == 'system')
			$('#message_box').append("<div class=\"system_msg\">"+msg.message+"</div>");
		
		$("#message_box").scrollTop($("#message_box")[0].scrollHeight);
	};
	
	websocket.onerror	= function(ev){$('#message_box').append("<div class=\"system_error\">Error Occurred - "+ev.data+"</div>");}; 
	websocket.onclose 	= function(ev){$('#message_box').append("<div class=\"system_msg\">Connection Closed</div>");}; 

	$("#username").keypress(function(e) {
	    if(e.which == 13) {	
			login();
	    }
	});

	$("#username").off("keydown").on("keydown",function(){
		$(this).parent().removeClass("error");
		$(this).parent().removeAttr("data-tooltip-text");
		$(this).parent().removeAttr("data-tooltip-placement");
		$(this).parent().removeAttr("data-tooltip-type");
	});
	
});

function userMessage(msg){
	var msgTimer = new Date();
	$('#message_box').append("<div><span>["+$.trim(msg.timestamp)+"] </span><span class='user_name'>"+msg.name+"</span> : <span class='user_message'>"+msg.message+"</span></div>");
}

function login(){
	if(websocket.readyState == websocket.OPEN){
		if($("#username").val() != ""){
			var msg = {
				type: 'entry',
				user: $("#username").val()
			};
			//convert and send data to server
			websocket.send(JSON.stringify(msg));
		}
		else{
			$("#username").parent().attr("data-tooltip-text","mandatory");
			$("#username").parent().attr("data-tooltip-placement","left");
			$("#username").parent().attr("data-tooltip-type","error");
			$("#username").parent().addClass("error");
		}
	}
	else{
		$("#nameInput").addClass("error");
		$("#nameInput").attr("data-tooltip-text","feather seems to be down. please try again later");
		$("#nameInput").attr("data-tooltip-placement","bottom");
		$("#nameInput").attr("data-tooltip-type","error");
	}
}
