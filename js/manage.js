function toggleBook( i ) {
	var div = document.getElementById("book-details-extra-" + i);
	var a = document.getElementById("book-edit-link-" + i);
	var img = document.getElementById("book-image-" + i);
	
	if ( div.style.display == "block" ) {
		a.innerHTML = "Edit &darr;";
		div.style.display = "none";
		img.className = "small";
	} else {
		a.innerHTML = "&uarr; Hide";
		div.style.display = "block";
		img.className = "";
	}
}

function addMeta( i ) {
	var thead = document.getElementById("book-meta-table-" + i);
	
	var tr = document.createElement("tr");
	var ktd = document.createElement("td");
	var vtd = document.createElement("td");
	
	var k = document.createElement("textarea");
	k.className = "key";
	k.name = "keys-" + i + "[]";
	
	var v = document.createElement("textarea");
	v.className = "value";
	v.name = "values-" + i + "[]";
	
	ktd.appendChild(k);
	vtd.appendChild(v);
	
	tr.appendChild(ktd);
	tr.appendChild(vtd);
	
	thead.appendChild(tr);
}

function reviewBigger( i ) {
	var text = document.getElementById("review-" + i);
	var height = text.style.height.substring(0, text.style.height.indexOf("px"));
	text.style.height = ( parseInt(height) + 75 ) + "px";
}

function reviewSmaller( i ) {
	var text = document.getElementById("review-" + i);
	var height = text.style.height.substring(0, text.style.height.indexOf("px"));
	if ( height - 75 > 0 )
		text.style.height = ( parseInt(height) - 75 ) + "px";
}

function setVisible() {
	// Hide book edit thingies on main Manage page.
	var divs = getElementsByClassName(document, "div", "book-details-extra");
	if ( divs != null ) {
		for ( var i = 0; i < divs.length; i++ ) {
			divs[i].style.display = 'none';
		}
	}
	
	// Show increase/decrease review size links on editsingle page.
	var reviewSizeLink = document.getElementById("review-size-link");
	if ( reviewSizeLink != null ) {
		reviewSizeLink.style.display = "block";
	}
}
addLoadEvent(setVisible);

function addLoadEvent(func) {
  var oldonload = window.onload;
  if ( typeof window.onload != 'function' ) {
    window.onload = func;
  } else {
    window.onload = function() {
      if ( oldonload ) {
        oldonload();
      }
      func();
    }
  }
}

function getElementsByClassName(oElm, strTagName, strClassName){
    var arrElements = (strTagName == "*" && document.all)? document.all : oElm.getElementsByTagName(strTagName);
    var arrReturnElements = new Array();
    strClassName = strClassName.replace(/\-/g, "\\-");
    var oRegExp = new RegExp("(^|\\s)" + strClassName + "(\\s|$)");
    var oElement;
    for ( var i=0; i<arrElements.length; i++){
        oElement = arrElements[i];
        if ( oRegExp.test(oElement.className)){
            arrReturnElements.push(oElement);
        }
    }
    return (arrReturnElements)
}