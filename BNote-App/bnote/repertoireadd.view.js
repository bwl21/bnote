sap.ui.jsview("bnote.repertoireadd", {
		
	getControllerName: function() {
		return "bnote.repertoireadd";
	},
	
	loadgenres: function(genres){
		this.genreitems.destroyItems();
		var path = "/genres/";
		for(var i=0; i < genres.getProperty(path).length; i++){
			var name = genres.getProperty(path + i + "/name");
			var key = genres.getProperty(path + i + "/id");
			this.genreitems.addItem(new sap.ui.core.Item({ text : name, key: key}));
		}
	},
	
	loadstatuses: function(statuses){
		this.statusitems.destroyItems();
		var path = "/status/";
		for(var i=1; i < statuses.getProperty(path).length; i++){
			var name = statuses.getProperty(path + i + "/name");
			var key = statuses.getProperty(path + i + "/id");
			this.statusitems.addItem(new sap.ui.core.Item({ text : name, key: key}));
		}
		
	},
	
	createContent: function(oController){
		var view = this;
		
		this.genreitems = new sap.m.Select({
			change: oController.setdirtyflag,
      	  	items: []
        }),

        this.statusitems = new sap.m.Select({
        	change: oController.setdirtyflag,
        	items: []
        }),
        
        
		this.repertoireaddForm = new sap.ui.layout.form.SimpleForm({
            title: "",
            content: [
              new sap.m.Label({text: "Name"}),
              new sap.m.Input({
            	  value: "{title}",
            	  change: oController.setdirtyflag,
            	  liveChange: validator.name
              }),                
              new sap.m.Label({text: "Komponist / Arrangeur"}),
              new sap.m.Input({
            	  value: "{composer}",
            	  change: oController.setdirtyflag,
            	  liveChange: validator.name
              }),              
              new sap.m.Label({text: "Länge"}),
              new sap.m.Input({
            	  value: "{length}",
            	  change: oController.setdirtyflag,
                 liveChange: validator.time
              }),              
              new sap.m.Label({text: "Tonart"}),
              new sap.m.Input({
            	  value: "{music_key}",
            	  change: oController.setdirtyflag,
             	  liveChange: validator.short_name
             	 
              }),              
              new sap.m.Label({text: "Genre"}),
              this.genreitems,
              
              new sap.m.Label({text: "Tempo (bpm)"}),
              new sap.m.Input({
            	  value: "{bpm}",
            	  change: oController.setdirtyflag,
              	  liveChange: validator.positive_amount
              }),              
              new sap.m.Label({text: "Notizen"}),
              new sap.m.Input({
            	  value: "{notes}",
            	  change: oController.setdirtyflag,            	  
              	  liveChange: validator.text
              }),              
              new sap.m.Label({text: "Status"}),
              this.statusitems,
            ]
		});
		
		var updateButton = new sap.m.Button({
			icon: sap.ui.core.IconPool.getIconURI("save"),
			press: oController.savechanges
		});
		
		var page = new sap.m.Page("RepertoireAddPage", {
	        title: "Song hinzufügen/ ändern",
	        showNavButton: true,
	        navButtonPress: function() {
	        	oController.checkdirtyflag();
	            app.back();
	        },
	        headerContent: [ updateButton ],
			content: [ this.repertoireaddForm ],
	        footer: [ getNaviBar() ]
		});
		return page;
	}	
});

	