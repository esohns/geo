/*NOTE*: this code was ripped from http://www.wolfpil.de/v3/cursor-tooltip.html */
var map;

function Tooltip(e)
{
 this.setValues(e);
	this.map_=e.map;
	var d=this.div_=document.createElement("div");
	d.className="tooltip";
	var c=google.maps;
	var b=this;
	var f=[c.event.addListener(this.map_,
	                           "mousemove",
																												function(a){
																												 b.getPos(a);
																													b.evt=a}
																												),
								c.event.addDomListener(document,
								                       "keydown",
																															function(a){
																															 if(!a)var a=window.event;
																																if(a.keyCode==16||a.which==16){
																																 b.keydown=true;
																																	if(b.over)b.addTip()
																																}
																															}),
								c.event.addDomListener(document,
								                       "keyup",
																															function(){
																															 b.keydown=false;
																																b.removeTip()
																															}),
							 c.event.addListener(this.map_,
								                    "mouseout",
																												function(){
																												 b.over=false;
																													b.set("visible",false)
																												}),
								c.event.addListener(this.map_,
								                    "mouseover",
																												function(){
																												 b.over=true;
																													if(b.keydown){
																													 b.set("visible",true)||b.addTip()
																													}
																												})]
}
Tooltip.prototype = {
 draw: function(){
	 if(typeof this.evt!="undefined"){
		 this.getPos(this.evt)
		}
	},
	visible_changed: function(){
	 var a=this.get("visible");
		this.div_.style.visibility=a?"visible":"hidden";
	},
	getPos: function(a){
	 if(a){
		 var e=this.getProjection();
			var d=this.div_;
			var c=e.fromLatLngToDivPixel(a.latLng);
			var b=a.latLng.lat();
			var f=a.latLng.lng();
			var j=this.map_.getZoom();
			b=b.toFixed(Math.round(j/4+1));
			f=f.toFixed(Math.round(j/4+1));
			d.innerHTML=b+", "+f;
			var g=7;
			var h=c.x+g;
			var k=c.y+g;
			var i=this.map_.getBounds().getNorthEast();
			i.pixel=e.fromLatLngToDivPixel(i);
			if(!this.width_)this.width_=90;
			if(this.width_+h>i.pixel.x){
			 h-=this.width_+g
			}
			d.style.left=h+"px";
			d.style.top=k+"px";
		}
	},
	addTip: function(){
	 this.set("visible",true);
		this.getPanes().floatPane.appendChild(this.div_);
		this.width_=this.div_.offsetWidth
	},
	removeTip: function(){
	 var a=this.div_.parentNode;
		if(a)a.removeChild(this.div_);
	}
};

function inherit(a,e){
 var d=e.prototype;
	var c=a.prototype;
	for(var b in d){
	 if(typeof c[b]=="undefined")c[b]=d[b]
	}
}
inherit(Tooltip,google.maps.OverlayView);
window.onload=function start(){
 var a=google.maps;
	var e={center:new a.LatLng(43.84442,-84.83642),
	       zoom:4,
								mapTypeId:a.MapTypeId.ROADMAP,
								scaleControl:true,
								draggableCursor:'auto',
								mapTypeControlOptions:{mapTypeIds:[a.MapTypeId.ROADMAP,a.MapTypeId.SATELLITE,a.MapTypeId.HYBRID]}};
 var d=document.getElementById("map");
	map=new a.Map(d,e);
	var c=new Reticule();
	c.index=1;
	a.event.addListener(map,"rightclick",function(){c.set("visible",!c.visible)});
	new Tooltip({map:map})
};

function Reticule(){
 this.img=document.createElement("img");
	this.img.src="http://sites.google.com/site/mxamples/cross.png";
	this.img.style.width="22px";
	this.img.style.height="22px";
	map.controls[google.maps.ControlPosition.BOTTOM_RIGHT].push(this.img);
	this.set("visible",false);
}
Reticule.prototype.set = function(a,e){
 this.img.style.display=e?"block":"none";
	this.visible=e;
	var d=map.getDiv();
	var c=d.offsetWidth/2-11;
	var b=d.offsetHeight/2-11;
	this.img.style.left=c+"px";
	this.img.style.top=b+"px"
};
