var mysql = require('./node_modules/mysql');
var config = require('./config.json');
var validator = require('./validation.js');

function formatErrorResponse(code, errs) {
	return JSON.stringify({ 
		error  : code,
		errors : errs
	});
}

exports.handler = (event, context, callback) => {
	//instruct the function to return as soon as the callback is invoked
	context.callbackWaitsForEmptyEventLoop = false;

	//validate input
	var errors = new Array();
	
	
	if(errors.length > 0) {
		// This should be a "Bad Request" error
		callback(formatErrorResponse('BAD_REQUEST', errors));
	} else {
	
	//getConnection equivalent
	var conn = mysql.createConnection({
		host 	: config.dbhost,
		user 	: config.dbuser,
		password : config.dbpassword,
		database : config.dbname
	});
	
	//attempts to connect to the database
	conn.connect(function(err) {
	  	
		if (err)  {
			// This should be a "Internal Server Error" error
			callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		};
		console.log("Connected!");
		var sql = "SELECT things.thingid, things.thingname, convert_tz(things.thingcreated,@@session.time_zone,'America/New_York') as thingcreated, things.thinguserid, things.thingattachmentid, things.thingregistrationcode, username, filename " +
                "FROM things LEFT JOIN users ON things.thinguserid = users.userid " +
                "LEFT JOIN attachments ON things.thingattachmentid = attachments.attachmentid " +
                "WHERE thingid = ?";
		
		conn.query(sql, [event.thingid], function (err, result) {
		  	if (err) {
				// This should be a "Internal Server Error" error
				callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		  	} else {
		  	
		  		
		  		// Pull out just the codes from the "result" array (index '1')
		  		if(!event.thingid){
		  			callback('BAD_REQUEST', "Thing does not exist");
		  		}
		  		
		  		var json = {
		  			
		  			thingid : event.thingid,
		  			thingregistrationcode : result[0].thingregistrationcode,
		  			thingname : result[0].thingname,
		  			date: result[0].thingcreated,
		  			userid: result[0].thinguserid,
		  			thingattachmentid : result[0].thingattachmentid,
		  			username : result[0].username,
		  			filename : result[0]['filename']
		  		}
				// Return the json object
      			callback(null, json);
      		setTimeout( function() {
						conn.end();
					}, 3000);
		  	}
		  	}); //query registration codes
		}); //connect database
	} //no validation errors
} //handler