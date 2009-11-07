
function eli18n(o) {
	
	var domain = null;
	
	this.load = function(msgs) {

		if (typeof(msgs) == 'object') {
			for (var i in msgs) {
				var d = msgs[i];
				if (typeof(d) == 'object') {
					if (!this.messages[i]) { this.messages[i] = {};	}
					if (!domain) { domain = i; }
					for (var k in d) { this.messages[i][k] = d[k]; };
				}
			}
		}
	}
	
	this.textdomain = function(d) {
		if (d && this.messages[d]) { domain = d; }
		return d;
	}
	
	this.translate = function(msg, d) {
		if (!d || !this.messages[d]) { d = domain; }
		return this.messages[domain] && this.messages[domain][msg] ? this.messages[domain][msg] : msg;
	}
	
	this.format = function(msg, data, translate, d) {
		msg = translate ? this.translate(msg, d) : msg;
		if (typeof(data) == 'object') {
			for (var i in data) {
				var v = translate ? this.translate(data[i]) : data[i];
				msg = msg.replace('%'+i, v);
			}
		}
		return msg;
	}
	
	o && o.messages   && this.load(o.messages);
	o && o.textdomain && this.textdomain(o.textdomain);
	
	
}

eli18n.prototype.messages = {};