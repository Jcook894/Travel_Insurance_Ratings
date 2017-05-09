
function CommentFormController(pLiveSiteUrl, pNameNeeded, pCommentNeeded, pCommentLength, pCommentLengthWarning, pRatingBounds, pMaxRating, pSecurityNeeded, pUseSecurity)
{
	this._LiveSiteUrl = pLiveSiteUrl;
    this._NameNeeded = pNameNeeded;
    this._CommentNeeded = pCommentNeeded;
	this._CommentLength = pCommentLength;
    this._CommentLengthWarning = pCommentLengthWarning;
    this._RatingBounds = pRatingBounds;
    this._MaxRating = pMaxRating;
    this._SecurityNeeded = pSecurityNeeded;
    this._UseSecurity = pUseSecurity;	
}

CommentFormController.prototype = 
{
	UpdateAvatar : function() 
	{       	
		var avatar = document.getElementById("commentavatar");
		var selectlist = document.getElementById("avatarselect");
	    if(selectlist.value =='-1')
	    {
	        avatar.src = this._LiveSiteUrl + "/noimage.png";
	        return;
	    }
		avatar.src = this._LiveSiteUrl + "/" + selectlist.value;
	},	
	
	CommentSubmitButton : function(pAllowUserComments)
	{
	
	    var form = document.commentForm;
	    var comment = form.comment.value;
	    var userRating = parseInt(form.userRating.value, 10);
	    comment = comment.replace(/^\s+/, '');
	    if(pAllowUserComments && form.anonymousName != null)
	    {
	      if (form.anonymousName.value == '')
	      {
	        alert(this._NameNeeded);
	        form.anonymousName.focus();
			return false;
		  }
	    }

	    if(form.requireUserRating.value == 1 &&(isNaN(userRating) || userRating < 0 || userRating > this._MaxRating))
	    {
	        alert( this._RatingBounds + this._MaxRating );
	        form.userRating.focus();
	        return false;
	    }		
		
	    if (comment == '')
	    {
			alert(this._CommentNeeded);
			form.comment.focus();
			return false;
		}
		if(comment.length > this._CommentLength)
		{
			alert( this._CommentLengthWarning + this._CommentLength);
			form.comment.focus();
			return false;
	    }
	
	    if(this._UseSecurity)
	    {
	       if (form.security_try.value=='')
	       {
		      alert(this._SecurityNeeded);
		      form.security_try.focus();
		      return false;
	       }
		}
	    else
	    {
	        return true;
	    }
	}	
}

