
var Game = {

	Socket: null,
	Online: false,
	Lamp:false,

	Init: function() {

		jQuery(document).bind('keydown',function(e){
			if(!Game.Online) return;

			switch(e.keyCode) {
				case 66: { Game.Send_Lamp('on'); break; }
				case 87: { Game.Send_Move('up'); break; }
				case 65: { Game.Send_Move('left'); break; }
				case 68: { Game.Send_Move('right'); break; }
				case 83: { Game.Send_Move('down'); break; }
			}

			return;
		});

		jQuery(document).bind('keyup',function(e){
			if(!Game.Online) return;

			switch(e.keyCode) {
				case 66: { Game.Send_Lamp('off'); break; }
			}

			return;
		});
	},

	Connect: function(host,port) {

		Game.Socket = new WebSocket(
			'ws://'+host+':'+port
		);

		Game.Socket.onopen = Game.OnConnect;
		Game.Socket.onerror = Game.OnError;
		Game.Socket.onmessage = Game.OnMessage;

		return;
	},

	OnConnect: function(e) {
		Game.ConsoleLog('You have connected to the server.');

		Game.Socket.send(JSON.stringify({
			"Type":"register"
		})+String.fromCharCode(10));

		return;
	},

	OnError: function(e) {
		document.write('ERROR: ' + JSON.stringify(e));
		return;
	},

	OnMessage: function(msg) {
		var obj = jQuery.parseJSON(msg.data);

		switch(obj.Type) {
			case 'welcome': { Game.Handle_Welcome(obj); break; }
			case 'join': { Game.Handle_Join(obj); break; }
			case 'leave': { Game.Handle_Leave(obj); break; }
			case 'move': { Game.Handle_Move(obj); break; }
			case 'lamp': { Game.Handle_Lamp(obj); break; }
		}

		return;
	},

	ConsoleLog: function(msg) {

		var date = new Date();
		var datestring = date.getHours()+':'+date.getMinutes()+':'+date.getSeconds();

		jQuery('#console')
		.prepend('['+datestring+'] '+msg+String.fromCharCode(10));

	},

	////////////////
	////////////////

	Handle_Welcome: function(obj) {
		Game.Online = true;
		Game.ConsoleLog('You have entered the jar.');
		Game.ConsoleLog('You are firefly #' + obj.ID);
		Game.SpawnFirefly(obj.ID,obj.Position[0],obj.Position[1],true);
		return;
	},

	Handle_Join: function(obj) {
		Game.ConsoleLog('Firefly ' + obj.ID + ' has entered the jar.');
		Game.SpawnFirefly(obj.ID,obj.Position[0],obj.Position[1],false);
		return;
	},

	Handle_Leave: function(obj) {
		Game.ConsoleLog('Firefly ' + obj.ID + ' has left the jar.');
		Game.DespawnFirefly(obj.ID);
		return;
	},

	Handle_Move: function(obj) {
		//Game.ConsoleLog('Firefly ' + obj.ID + ' has moved.');
		jQuery('#firefly-'+obj.ID).css({
			'top':obj.Position[1]+'px',
			'left':obj.Position[0]+'px'
		});
		return;
	},

	Handle_Lamp: function(obj) {
		if(obj.State == 'on') {
			Game.ConsoleLog('Firefly ' + obj.ID + ' is a bright bug.');
			jQuery('#firefly-'+obj.ID+' > div').fadeIn('fast');
		} else {
			Game.ConsoleLog('Firefly ' + obj.ID + ' is rather dim.');
			jQuery('#firefly-'+obj.ID+' > div').fadeOut('fast');
		}


		return;
	},

	////////////////
	////////////////

	Send_Move: function(dir) {

		Game.Socket.send(JSON.stringify({
			"Type":"move",
			"Dir":dir
		}));

		return;
	},

	Send_Lamp: function(state) {
		if(state == 'on' && Game.Lamp) return;

		Game.Socket.send(JSON.stringify({
			"Type":'lamp',
			"State":((state=='on')?('on'):('off'))
		}));

		Game.Lamp = ((state=='on')?(true):(false));

		return;
	},

	////////////////
	////////////////

	Players: [],
	SpawnFirefly: function(id,x,y,player) {
		Game.Players[id] = {
			X:x,
			Y:y
		}

		jQuery('#jar')
		.append('<div id="firefly-' + id + '" class="firefly">' + id + '<div></div></div>');

		jQuery('#firefly-'+id)
		.addClass((player)?('player'):('other'))
		.css({'top':y+'px','left':x+'px'})
		.fadeIn('slow');

		return;
	},

	DespawnFirefly: function(id) {

		jQuery('#firefly-'+id)
		.fadeOut('slow')
		.queue(function(){
			jQuery(this).dequeue();
			jQuery(this).remove();
		});

		return;
	}

};
