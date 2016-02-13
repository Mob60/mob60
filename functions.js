/******** Debut du script du menu ************/
var timer = null;
var objOut = null;
 
function mouseOut(obj)
{
   var elmt = document.getElementById(obj.id+"Item");
   elmt.style.display = "none";
   
   document.getElementById(obj.id+"Titre").className = "titreMenu itemOut";
   //document.getElementById(obj.id+"Titre").style.borderStyle="outset";
 
   //instruction a placer a la fin de la fonction
   timer = null;
}
 
function fout(obj)
{
   objOut = obj;
   if (timer==null)
   {
      timer = setTimeout("mouseOut(objOut)","1000");
   }
}
 
 
function fover(obj)
{
   var elmt, elmtTitre;
   clearTimeout(timer);
   timer = null;
 
   elmt = document.getElementById(obj.id+"Item");
   elmt.style.display = "block";
 
   elmtTitre = document.getElementById(obj.id+"Titre");
   
   elmtTitre.className = "titreMenu itemOver";
   //elmtTitre.style.borderStyle = "inset";
   
   if (document.all==null) //si on est sous IE pas besoin d'ajuster les positions
      elmt.style.left = elmtTitre.offsetLeft+elmtTitre.clientWidth+"px";
 
   if (objOut!=obj && objOut!=null)
   {
      mouseOut(objOut);
   }
}