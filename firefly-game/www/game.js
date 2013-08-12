
var Game = {

	Socket: null,
	Online: false,

	Init: function() {
		jQuery(document).bind('keypress',function(e){
			if(!Game.Online) return;

			// 119 w
			// 97  a
			// 115 s
			// 100 d

			switch(e.keyCode) {
				case 119: { Game.Send_Move('up'); break; }
				case 97: { Game.Send_Move('left'); break; }
				case 115: { Game.Send_Move('down'); break; }
				case 100: { Game.Send_Move('right'); break; }
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
		Game.SpawnFirefly(obj.ID,obj.Position[0],obj.Position[1]);
		return;
	},

	Handle_Join: function(obj) {
		Game.ConsoleLog('Firefly ' + obj.ID + ' has entered the jar.');
		return;
	},

	Handle_Leave: function(obj) {
		Game.ConsoleLog('Firefly ' + obj.ID + ' has left the jar.');
		return;
	},

	Handle_Move: function(obj) {
		Game.ConsoleLog('Firefly ' + obj.ID + ' has moved.');
		jQuery('#firefly-'+obj.ID).css({
			'top':obj.Position[1]+'px',
			'left':obj.Position[0]+'px'
		});
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

	////////////////
	////////////////

	Players: [],
	SpawnFirefly: function(id,x,y) {
		Game.Players[id] = {
			X:x,
			Y:y
		}

		jQuery('#jar').append(
			'<div id="firefly-' + id + '" class="firefly"></div>'
		);
	}

};
