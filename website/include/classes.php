<?php

if (file_exists(getcwd() . "/include/credentials.php")) {
    require('credentials.php');
} else {
    echo "Application has not been configured. Copy and edit the credentials-sample.php file to credentials.php.";
    exit();
}

class Application {

    public $debugMessages = [];

    public function setup() {

        // Check to see if the client has a cookie called "debug" with a value of "true"
        // If it does, turn on error reporting
        if ($_COOKIE['debug'] == "true") {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }
    }

    // Writes a message to the debug message array for printing in the footer.
    public function debug($message) {
        $this->debugMessages[] = $message;
    }


    public function auditlog($context, $message, $priority = 0, $userid = NULL){

        // Declare an errors array
        $errors = [];


        // If a user is logged in, get their userid
        if ($userid == NULL) {

            $user = $this->getSessionUser($errors, TRUE);
            if ($user != NULL) {
                $userid = $user["userid"];
            }

        }

        $ipaddress = $_SERVER["REMOTE_ADDR"];

        if (is_array($message)){
            $message = implode( ",", $message);
        }

        $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/auditlog";
  			$data = array(
  				'context'=>$context,
  				'message'=>$message,
  				'ipaddress'=>$ipaddress,
  				'userid'=>$userid
  			);
  			$data_json = json_encode($data);

  			$ch = curl_init();
  			curl_setopt($ch, CURLOPT_URL, $url);
  			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
  			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
  			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
  			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  			$response  = curl_exec($ch);
  			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  			if ($response === FALSE) {
  				$errors[] = "An unexpected failure occurred contacting the web service.";
  			} else {

  				if($httpCode == 400) {

  					// JSON was double-encoded, so it needs to be double decoded
  					$errorsList = json_decode(json_decode($response))->errors;
  					foreach ($errorsList as $err) {
  						$errors[] = $err;
  					}
  					if (sizeof($errors) == 0) {
  						$errors[] = "Bad input";
  					}

  				} else if($httpCode == 500) {

  					$errorsList = json_decode(json_decode($response))->errors;
  					foreach ($errorsList as $err) {
  						$errors[] = $err;
  					}
  					if (sizeof($errors) == 0) {
  						$errors[] = "Server error";
  					}

  				} else if($httpCode == 200) {



  				}

  			}


  			curl_close($ch);

    }

    protected function validateUsername($username, &$errors) {
        if (empty($username)) {
            $errors[] = "Missing username";
        } else if (strlen(trim($username)) < 3) {
            $errors[] = "Username must be at least 3 characters";
        } else if (strpos($username, "@")) {
            $errors[] = "Username may not contain an '@' sign";
        }
    }

    protected function validatePassword($password, &$errors) {
        if (empty($password)) {
            $errors[] = "Missing password";
        } else if (strlen(trim($password)) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }
    }

    protected function validateEmail($email, &$errors) {
        if (empty($email)) {
            $errors[] = "Missing email";
        } else if (substr(strtolower(trim($email)), -20)
            && substr(strtolower(trim($email)), -13) != "@thackston.me") {
                // Verify it's a Georgia Southern email address
              //  $errors[] = "Not a Georgia Southern email address";
            }
    }


    // Registers a new user
    public function register($username, $password, $email, $registrationcode, &$errors) {

        $this->auditlog("register", "attempt: $username, $email, $registrationcode");

        // Validate the user input
        $this->validateUsername($username, $errors);
        $this->validatePassword($password, $errors);
        $this->validateEmail($email, $errors);
        if (empty($registrationcode)) {
            $errors[] = "Missing registration code";
        }

        // Only try to insert the data into the database if there are no validation errors
        if (sizeof($errors) == 0) {

            // Hash the user's password
            $passwordhash = password_hash($password, PASSWORD_DEFAULT);

            // Create a new user ID
            $userid = bin2hex(random_bytes(16));

			$url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/registeruser";
			$data = array(
				'userid'=>$userid,
				'username'=>$username,
				'passwordHash'=>$passwordhash,
				'email'=>$email,
				'registrationcode'=>$registrationcode
			);
			$data_json = json_encode($data);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response  = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if ($response === FALSE) {
				$errors[] = "An unexpected failure occurred contacting the web service.";
			} else {

				if($httpCode == 400) {

					// JSON was double-encoded, so it needs to be double decoded
					$errorsList = json_decode(json_decode($response))->errors;
					foreach ($errorsList as $err) {
						$errors[] = $err;
					}
					if (sizeof($errors) == 0) {
						$errors[] = "Bad input";
					}

				} else if($httpCode == 500) {

					$errorsList = json_decode(json_decode($response))->errors;
					foreach ($errorsList as $err) {
						$errors[] = $err;
					}
					if (sizeof($errors) == 0) {
						$errors[] = "Server error";
					}

				} else if($httpCode == 200) {

					 $this->sendValidationEmail($userid, $email, $errors);

				}

			}


			curl_close($ch);

        } else {
            $this->auditlog("register validation error", $errors);
        }

        // Return TRUE if there are no errors, otherwise return FALSE
        if (sizeof($errors) == 0){
            return TRUE;
        } else {
            return FALSE;
        }
    }


    // Send an email to validate the address
    protected function sendValidationEmail($userid, $email, &$errors) {


        $this->auditlog("sendValidationEmail", "Sending message to $email");

        $validationid = bin2hex(random_bytes(16));

        $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/sendValidationEmail";
        $data = array(
          'emailvalidationid'=>$validationid,
          'userid'=>$userid,
          'email'=>$email
        );
        $data_json = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === FALSE) {
          $errors[] = "An unexpected failure occurred contacting the web service.";
        } else {

          if($httpCode == 400) {

            // JSON was double-encoded, so it needs to be double decoded
            $errorsList = json_decode(json_decode($response))->errors;
            foreach ($errorsList as $err) {
              $errors[] = $err;
            }
            if (sizeof($errors) == 0) {
              $errors[] = "Bad input";
            }

          } else if($httpCode == 500) {

            $errorsList = json_decode(json_decode($response))->errors;
            foreach ($errorsList as $err) {
              $errors[] = $err;
            }
            if (sizeof($errors) == 0) {
              $errors[] = "Server error";
            }

          } else if($httpCode == 200) {

            $this->auditlog("sendValidationEmail", "Sending message to $email");

            // Send reset email
            $pageLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $pageLink = str_replace("register.php", "login.php", $pageLink);
            $to      = $email;
            $subject = 'Please confirm your email!';
            $message = "A request has been made to create an account at Photofolio Spectatular for this email address. ".
                "If you did not make this request, please ignore this message. No other action is necessary. ".
                "To confirm this address, please click the following link: $pageLink?id=$validationid";
            $headers = 'From: photofoliodeveloper@photofolio.me' . "\r\n" .
                'Reply-To: sp05337@georgiasouthern.edu' . "\r\n";

            mail($to, $subject, $message, $headers);

            $this->auditlog("sendValidationEmail", "Message sent to $email");

          }

        }

    }

    // Send an email to validate the address
    public function processEmailValidation($validationid, &$errors) {


        $success = FALSE;

        $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/processEmailValidation";
        $data = array(
          'emailvalidationid'=>$validationid,
        );
        $data_json = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);


        if ($response === FALSE) {
          $errors[] = "An unexpected failure occurred contacting the web service.";
        } else {
          $this->auditlog("processEmailValidation", "Received: $validationid");
          if($httpCode == 400) {

            // JSON was double-encoded, so it needs to be double decoded
            $errorsList = json_decode(json_decode($response))->errors;
            foreach ($errorsList as $err) {
              $errors[] = $err;
            }
            if (sizeof($errors) == 0) {
              $errors[] = "Bad input";
            }

          } else if($httpCode == 500) {

            $errorsList = json_decode(json_decode($response))->errors;
            foreach ($errorsList as $err) {
              $errors[] = $err;
            }
            if (sizeof($errors) == 0) {
              $errors[] = "Server error";
            }

          } else if($httpCode == 200) {

            $this->auditlog("processEmailValidation", "Email address validated: $validationid");
            $success = TRUE;


          }

        }


        return $success;

    }

    // Creates a new session in the database for the specified user
    public function newSession($userid, &$errors, $registrationcode = NULL) {

        // Check for a valid userid
        if (empty($userid)) {
            $errors[] = "Missing userid";
            $this->auditlog("session", "missing userid");
        }

        // Only try to query the data into the database if there are no validation errors
        if (sizeof($errors) == 0) {

            if ($registrationcode == NULL) {
                $regs = $this->getUserRegistrations($userid, $errors, $regs);

                $this->auditlog("session", "logging in user with first reg code $reg");
                $registrationcode = $regs[0];
            }

            // Create a new session ID
            $sessionid = bin2hex(random_bytes(25));

            $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/newSession";
      			$data = array(
      				'usersessionid'=>$sessionid,
      				'userid'=>$userid,
      				'registrationcode'=>$registrationcode
      			);
      			$data_json = json_encode($data);

      			$ch = curl_init();
      			curl_setopt($ch, CURLOPT_URL, $url);
      			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
      			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
      			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
      			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      			$response  = curl_exec($ch);
      			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      			if ($response === FALSE) {
      				$errors[] = "An unexpected failure occurred contacting the web service.";
      			} else {

      				if($httpCode == 400) {

      					// JSON was double-encoded, so it needs to be double decoded
      					$errorsList = json_decode(json_decode($response))->errors;
      					foreach ($errorsList as $err) {
      						$errors[] = $err;
      					}
      					if (sizeof($errors) == 0) {
      						$errors[] = "Bad input";
      					}

      				} else if($httpCode == 500) {

      					$errorsList = json_decode(json_decode($response))->errors;
      					foreach ($errorsList as $err) {
      						$errors[] = $err;
      					}
      					if (sizeof($errors) == 0) {
      						$errors[] = "Server error";
                  $this->auditlog("getUserRegistrations", "failed => $response");
      					}

      				} else if($httpCode == 200) {

                // Store the session ID as a cookie in the browser
                setcookie('sessionid', $sessionid, time()+60*60*24*30);
                $this->auditlog("session", "new session id: $sessionid for user = $userid");

                // Return the session ID
                return $sessionid;


      				}

      			}


      			curl_close($ch);


        }

    }

    public function getUserRegistrations($userid, &$errors) {

      // Assume an empty list of regs
        $regs = array();

		$url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/userregistrations?userid=$userid";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response  = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $user = json_decode($response);
    //print_r($user);

		if ($response === FALSE) {
			$errors[] = "An unexpected failure occurred contacting the web service.";
		} else {

			if($httpCode == 400) {

				// JSON was double-encoded, so it needs to be double decoded
				$errorsList = json_decode(json_decode($response))->errors;
				foreach ($errorsList as $err) {
					$errors[] = $err;
				}
				if (sizeof($errors) == 0) {
					$errors[] = "Bad input";
				}

			} else if($httpCode == 500) {

				$errorsList = json_decode(json_decode($response))->errors;
				foreach ($errorsList as $err) {
					$errors[] = $err;
				}
				if (sizeof($errors) == 0) {
					$errors[] = "Server error";
           $this->auditlog("getUserRegistrations", "failed => $response");
				}

			} else if($httpCode == 200) {

        $this->auditlog("getUserRegistrations", "success");
	            $this->auditlog("getUserRegistrations", "web service response => " . $response);
				$regs = json_decode($response)->userregistrations;
		        $this->auditlog("getUserRegistrations", "success");

			}

		}

		curl_close($ch);

        // Return the list of users
        return $regs;
    }
    // Updates a single user in the database and will return the $errors array listing any errors encountered
    public function updateUserPassword($userid, $password, &$errors) {

        // Validate the user input
        if (empty($userid)) {
            $errors[] = "Missing userid";
        }
        $this->validatePassword($password, $errors);


        if(sizeof($errors) == 0) {

          $passwordhash = password_hash($password, PASSWORD_DEFAULT);

          $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/updateUserPassword";
    			$data = array(
    				'userid'=>$userid,
    				'passwordhash'=>$passwordhash
    			);
    			$data_json = json_encode($data);

    			$ch = curl_init();
    			curl_setopt($ch, CURLOPT_URL, $url);
    			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
    			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    			$response  = curl_exec($ch);
    			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    			if ($response === FALSE) {
    				$errors[] = "An unexpected failure occurred contacting the web service.";
    			} else {

    				if($httpCode == 400) {

    					// JSON was double-encoded, so it needs to be double decoded
    					$errorsList = json_decode(json_decode($response))->errors;
    					foreach ($errorsList as $err) {
    						$errors[] = $err;
    					}
    					if (sizeof($errors) == 0) {
    						$errors[] = "Bad input";
    					}

    				} else if($httpCode == 500) {

    					$errorsList = json_decode(json_decode($response))->errors;
    					foreach ($errorsList as $err) {
    						$errors[] = $err;
    					}
    					if (sizeof($errors) == 0) {
    						$errors[] = "Server error";
    					}

    				} else if($httpCode == 200) {

    					 $this->auditlog("updateUserPassword", "success");

    				}

    			}
          curl_close($ch);

        } else {

            $this->auditlog("updateUserPassword validation error", $errors);

        }

        // Return TRUE if there are no errors, otherwise return FALSE
        if (sizeof($errors) == 0){
            return TRUE;
        } else {
            return FALSE;
        }
    }

    // Removes the specified password reset entry in the database, as well as any expired ones
    // Does not retrun errors, as the user should not be informed of these problems
    protected function clearPasswordResetRecords($passwordresetid) {

      $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/clearPasswordResetRecords?passwordresetid=$passwordresetid";
      $data = array(
        'passwordresetid'=>$passwordresetid
      );
      $data_json = json_encode($data);
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $response  = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);


      if ($response === FALSE) {
        $errors[] = "An unexpected failure occurred contacting the web service.";
      } else {

        if($httpCode == 400) {

          // JSON was double-encoded, so it needs to be double decoded
          $errorsList = json_decode(json_decode($response))->errors;
          foreach ($errorsList as $err) {
            $errors[] = $err;
          }
          if (sizeof($errors) == 0) {
            $errors[] = "Bad input";
          }

        } else if($httpCode == 500) {

          $errorsList = json_decode(json_decode($response))->errors;
          foreach ($errorsList as $err) {
            $errors[] = $err;
          }
          if (sizeof($errors) == 0) {
            $errors[] = "Server error";
          }

        } else if($httpCode == 200) {


        }

      }

      curl_close($ch);


    }

    // Retrieves an existing session from the database for the specified user
    public function getSessionUser(&$errors, $suppressLog=FALSE) {

        // Get the session id cookie from the browser
        $sessionid = NULL;
        $user = NULL;

        // Check for a valid session ID
        if (isset($_COOKIE['sessionid'])) {

            $sessionid = $_COOKIE['sessionid'];
            $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/getSessionUser?usersessionid=$sessionid";

        		$ch = curl_init();
        		curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
        		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        		$response  = curl_exec($ch);
        		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $scan = json_decode($response);


        		if ($response === FALSE) {
        			$errors[] = "An unexpected failure occurred contacting the web service.";
        		} else {

        			if($httpCode == 400) {

        				// JSON was double-encoded, so it needs to be double decoded
        				$errorsList = json_decode(json_decode($response))->errors;
        				foreach ($errorsList as $err) {
        					$errors[] = $err;
        				}
        				if (sizeof($errors) == 0) {
        					$errors[] = "Bad input";
        				}

        			} else if($httpCode == 500) {

        				$errorsList = json_decode(json_decode($response))->errors;
        				foreach ($errorsList as $err) {
        					$errors[] = $err;
        				}
        				if (sizeof($errors) == 0) {
        					$errors[] = "Server error";
                   $this->auditlog("getSessionUser", "failed => $response");
        				}

        			} else if($httpCode == 200) {

              $user = array(
              "userid"=>$scan->userid,
              "registrationcode"=>$scan->registrationcode,
              "isadmin"=>$scan->isadmin
            );
            $userid = $user["userid"];
            $isAdmin = $user["isadmin"];

        			}

        		}

        		curl_close($ch);


        }

        return $user;

    }

    // Retrieves an existing session from the database for the specified user
    public function isAdmin(&$errors, $userid) {


        // Check for a valid user ID
        if (empty($userid)) {
            $errors[] = "Missing userid";
            return FALSE;
        }

        $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/isadmin";
          $data = array(
            'userid'=>$userid
          );
          $data_json = json_encode($data);

          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
          curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $response  = curl_exec($ch);
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      if ($response === FALSE) {
        $errors[] = "An unexpected failure occurred contacting the web service.";
      } else {



        if($httpCode == 400) {

          // JSON was double-encoded, so it needs to be double decoded
          $errorsList = json_decode(json_decode($response))->errors;
          foreach ($errorsList as $err) {
            $errors[] = $err;
          }

          if (sizeof($errors) == 0) {
            $errors[] = "Bad input";
          }

        } else if($httpCode == 500) {

          $errorsList = json_decode(json_decode($response))->errors;
          foreach ($errorsList as $err) {
            $errors[] = $err;
          }
          if (sizeof($errors) == 0) {
            $errors[] = "Server error";
          }

        } else if($httpCode == 200) {
          $user = $this->getSessionUser($errors);

          $isadmin = $user['isadmin'];
          // Return the isAdmin flag
          return $isadmin == 1;

        }
      }
      curl_close($ch);



    }


    // Logs in an existing user and will return the $errors array listing any errors encountered
    public function login($username, $password, &$errors) {

        $this->debug("Login attempted");
        $this->auditlog("login", "attempt: $username, password length = ".strlen($password));

        // Validate the user input
        if (empty($username)) {
            $errors[] = "Missing username";
        }
        if (empty($password)) {
            $errors[] = "Missing password";
        }

        // Only try to query the data into the database if there are no validation errors
        if (sizeof($errors) == 0) {



              $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/login";
          			$data = array(
          				'username'=>$username
          			);
          			$data_json = json_encode($data);

          			$ch = curl_init();
          			curl_setopt($ch, CURLOPT_URL, $url);
          			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
          			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
          			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
          			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          			$response  = curl_exec($ch);
          			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $user = json_decode($response);


                  $passwordhash = $user->passwordhash;
                  $emailvalidated = $user->emailvalidated;
                  $userid = $user->userid;
                  $email = $user->email;



            if ($response === FALSE) {
      				$errors[] = "An unexpected failure occurred contacting the web service.";
      			} else {



      				if($httpCode == 400) {

      					// JSON was double-encoded, so it needs to be double decoded
      					$errorsList = json_decode(json_decode($response))->errors;
      					foreach ($errorsList as $err) {
      						$errors[] = $err;
      					}

      					if (sizeof($errors) == 0) {
      						$errors[] = "Bad input";
      					}

      				} else if($httpCode == 500) {

      					$errorsList = json_decode(json_decode($response))->errors;
      					foreach ($errorsList as $err) {
      						$errors[] = $err;
      					}
      					if (sizeof($errors) == 0) {
      						$errors[] = "Server error";
      					}

      				} else if($httpCode == 200) {

                if (!password_verify($password, $passwordhash)) {

                      $errors[] = "Bad username/password combination";
                      $this->auditlog("login", "bad password: password length = ".strlen($password));

                  } else if ($emailvalidated == 0) {

                      $errors[] = "Login error. Email not validated. Please check your inbox and/or spam folder.";

                  } else {
                    $otp = rand(100000,999999);
                    $to = $email;
                    $message = "OTP Login Authentication: " . $otp;
                    $headers = 'From: sp05337@georgiasouthern.edu' . "\r\n" .
                        'Reply-To: sp05337@georgiasouthern.edu' . "\r\n";

                    mail($to, $subject, $message, $headers);


                  		$message = "Check your email and submit OTP";
                     $this->newSession($userid, $errors);
                     $this->auditlog("login", "success: $username, $userid");

                     $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/validateotp";
              			$data = array(
              				'otp'=>$otp,
              				'userid'=>$userid,
              				'email'=>$email
              			);

                   $data_json = json_encode($data);

             			$ch = curl_init();
             			curl_setopt($ch, CURLOPT_URL, $url);
             			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
             			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
             			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
             			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
             			$response  = curl_exec($ch);
             			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

             			if ($response === FALSE) {
             				$errors[] = "An unexpected failure occurred contacting the web service.";
             			} else {
                     if($httpCode == 400) {

             					// JSON was double-encoded, so it needs to be double decoded
             					$errorsList = json_decode(json_decode($response))->errors;
             					foreach ($errorsList as $err) {
             						$errors[] = $err;
             					}
             					if (sizeof($errors) == 0) {
             						$errors[] = "Bad input";
             					}
             				}else if($httpCode == 500) {

             					$errorsList = json_decode(json_decode($response))->errors;
             					foreach ($errorsList as $err) {
             						$errors[] = $err;
             					}
             					if (sizeof($errors) == 0) {
             						$errors[] = "Server error";
             					}

             				} else if($httpCode == 200) {



             				}
                   }
                  }


      			}

          }




            curl_close($ch);
         }

         else {
            $this->auditlog("login validation error", $errors);
        }
        // Return TRUE if there are no errors, otherwise return FALSE
        if (sizeof($errors) == 0){
            return TRUE;
        } else {
            return FALSE;
        }
      }





    public function otpValidate($otp, $errors){
      // Only try to insert the data into the database if there are no validation errors
      if (sizeof($errors) == 0) {

        $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/otpinput";
       $data = array(
         'otp'=>$otp,
         'userid'=>$userid,
         'email'=>$email
       );

       $data_json = json_encode($data);

       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
       curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
       curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       $response  = curl_exec($ch);
       $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

       if ($response === FALSE) {
         $errors[] = "An unexpected failure occurred contacting the web service.";
       } else {

         if($httpCode == 400) {

 					// JSON was double-encoded, so it needs to be double decoded
 					$errorsList = json_decode(json_decode($response))->errors;
 					foreach ($errorsList as $err) {
 						$errors[] = $err;
 					}
 					if (sizeof($errors) == 0) {
 						$errors[] = "Bad input";
 					}

 				} else if($httpCode == 500) {

           $errorsList = json_decode(json_decode($response))->errors;
           foreach ($errorsList as $err) {
             $errors[] = $err;
           }
           if (sizeof($errors) == 0) {
             $errors[] = "Server error";
           }

         } else if($httpCode == 200) {

           $this->auditlog("otp validation", "success");

         }
      }


    curl_close($ch);

      } else {
          $this->auditlog("otp validation error", $errors);
      }

      // Return TRUE if there are no errors, otherwise return FALSE
      if (sizeof($errors) == 0){
          return TRUE;
      } else {
          return FALSE;
      }
  }



    // Logs out the current user based on session ID
    public function logout() {

        $sessionid = $_COOKIE['sessionid'];

        // Only try to query the data into the database if there are no validation errors
        if (!empty($sessionid)) {

            // Connect to the database

            $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/logout?usersessionid=$sessionid";
          /*  $data = array(
      				'usersessionid'=>$sessionid
      			);*/
      		//	$data_json = json_encode($data);
        		$ch = curl_init();
        		curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9'));
        		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            //curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
        		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        		$response  = curl_exec($ch);
        		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);


        		if ($response === FALSE) {
        			$errors[] = "An unexpected failure occurred contacting the web service.";
        		} else {

        			if($httpCode == 400) {

        				// JSON was double-encoded, so it needs to be double decoded
        				$errorsList = json_decode(json_decode($response))->errors;
        				foreach ($errorsList as $err) {
        					$errors[] = $err;
        				}
        				if (sizeof($errors) == 0) {
        					$errors[] = "Bad input";
        				}

        			} else if($httpCode == 500) {

        				$errorsList = json_decode(json_decode($response))->errors;
        				foreach ($errorsList as $err) {
        					$errors[] = $err;
        				}
        				if (sizeof($errors) == 0) {
        					$errors[] = "Server error";
        				}

        			} else if($httpCode == 200) {

                // Clear the session ID cookie
                setcookie('sessionid', '', time()-3600);
                $this->auditlog("logout", "successful: $sessionid");

        			}

        		}

        		curl_close($ch);

        }

    }


    // Checks for logged in user and redirects to login if not found with "page=protected" indicator in URL.
    public function protectPage(&$errors, $isAdmin = FALSE) {

        // Get the user ID from the session record
        $user = $this->getSessionUser($errors);
        $userid = $user["userid"];
        if ($user == NULL) {
            // Redirect the user to the login page
            $this->auditlog("protect page", "no user");
            header("Location: login.php?page=protected");
            exit();
        }


        // If there is no user ID in the session, then the user is not logged in
        if(empty($userid)) {

            // Redirect the user to the login page
            $this->auditlog("protect page error", $user);
            header("Location: login.php?page=protected");
            exit();

        } else if ($isAdmin)  {

            // Get the isAdmin flag from the database
            $isAdminDB = $this->isAdmin($errors, $userid);

            if (!$isAdminDB) {

                // Redirect the user to the home page
                $this->auditlog("protect page", "not admin");
                header("Location: index.php?page=protectedAdmin");
                exit();

            }

        }

    }

    // Get a list of things from the database and will return the $errors array listing any errors encountered
    public function getThings(&$errors) {

        // Assume an empty list of things
        $things = array();

        // Get the user id from the session
        $user = $this->getSessionUser($errors);
        $registrationcode = $user["registrationcode"];

       $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/getThings?thingregistrationcode=$registrationcode";

    		$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
    		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    		$response  = curl_exec($ch);
    		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $scan = json_decode($response);
        //print_r($scan);

    		if ($response === FALSE) {
    			$errors[] = "An unexpected failure occurred contacting the web service.";
    		} else {

    			if($httpCode == 400) {

    				// JSON was double-encoded, so it needs to be double decoded
    				$errorsList = json_decode(json_decode($response))->errors;
    				foreach ($errorsList as $err) {
    					$errors[] = $err;
    				}
    				if (sizeof($errors) == 0) {
    					$errors[] = "Bad input";
              $this->auditlog("getThings", "failed => $response");
    				}

    			} else if($httpCode == 500) {

    				$errorsList = json_decode(json_decode($response))->errors;
    				foreach ($errorsList as $err) {
    					$errors[] = $err;
    				}
    				if (sizeof($errors) == 0) {
    					$errors[] = "Server error";
               $this->auditlog("getThings", "failed => $response");
    				}

    			} else if($httpCode == 200) {

            $this->auditlog("getThings", "success");
            $scatter = $scan->things;
            foreach($scatter as $scat){
              $scat = array(
                'thingid'=>$scat->thingid,
                'thingname'=>$scat->thingname,
                'thingcreated'=>$scat->date,
                'userid'=>$scat->userid,
                'attachmentid'=>$scat->attachmentid,
                'registrationcode'=>$scat->registrationcode
              );
              $things[] = $scat;

            }

    			}

    		}

    		curl_close($ch);

        // Return the list of things
        return $things;

    }

    // Get a single thing from the database and will return the $errors array listing any errors encountered
    public function getThing($thingid, &$errors) {

        // Assume no thing exists for this thing id
        $thing = NULL;

        // Check for a valid thing ID
        if (empty($thingid)){
            $errors[] = "Missing thing ID";
        }

        if (sizeof($errors) == 0){

          $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/getThing?thingid=$thingid";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response  = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $scan = json_decode($response);


            if ($response === FALSE) {
              $errors[] = "An unexpected failure occurred contacting the web service.";
            } else {

              if($httpCode == 400) {

                // JSON was double-encoded, so it needs to be double decoded
                $errorsList = json_decode(json_decode($response))->errors;
                foreach ($errorsList as $err) {
                  $errors[] = $err;
                }
                if (sizeof($errors) == 0) {
                  $errors[] = "Bad input";
                }

              } else if($httpCode == 500) {

                $errorsList = json_decode(json_decode($response))->errors;
                foreach ($errorsList as $err) {
                  $errors[] = $err;
                }
                if (sizeof($errors) == 0) {
                  $errors[] = "Server error";
                   $this->auditlog("getThings", "failed => $response");
                }

              } else if($httpCode == 200) {

                $this->auditlog("getThing", "success: $thingid");

                $thing = array(
                      'thingid'=>$scan->thingid,
                      'thingname'=>$scan->thingname,
                      'thingcreated' => $scan->date,
                      'registrationcode' => $scan->thingregistrationcode,
                      'attachmentid' => $scan->thingattachmentid,
                      'filename' => $scan->filename,
                      'username' => $scan->username,
                      'userid' => $scan->thinguserid
                    );
              }

            }

            curl_close($ch);


        } else {
            $this->auditlog("getThing validation error", $errors);
        }

        // Return the thing
        return $thing;

    }

    // Get a list of comments from the database
    public function getComments($thingid, &$errors) {

        // Assume an empty list of comments
        $comments = array();

        // Check for a valid thing ID
        if (empty($thingid)) {

            // Add an appropriate error message to the list
            $errors[] = "Missing thing ID";
            $this->auditlog("getComments validation error", $errors);

        } else {

          $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/getComments?commentthingid=$thingid";

          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
           curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $response  = curl_exec($ch);
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
           $scan = json_decode($response);
           //print_r($scan);

          if ($response === FALSE) {
            $errors[] = "An unexpected failure occurred contacting the web service.";
          } else {

            if($httpCode == 400) {

              // JSON was double-encoded, so it needs to be double decoded
              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Bad input";
                 $this->auditlog("getComments", "failed => $response");
              }

            } else if($httpCode == 500) {

              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Server error";
                  $this->auditlog("getComments", "failed => $response");
              }

            } else if($httpCode == 200) {

               $this->auditlog("getComments", "success");
               $scatter = $scan->comments;
               foreach($scatter as $scat){
                    $scat = array(
                            'commentthingid'=>$scatter->commentthingid,
                            'commentid'=> $scat->commentid,
                           'commenttext'=> $scat->commenttext,
                           'commentposted'=> $scat->date,
                           'username'=> $scat->username,
                           'attachmentid'=> $scat->attachmentid,
                           'filename'=> $scat->filename
                   );
                   $comments[] = $scat;
                 }
            }

          }

          curl_close($ch);

        }

        // Return the list of comments
        return $comments;

    }

    // Handles the saving of uploaded attachments and the creation of a corresponding record in the attachments table.
    public function saveAttachment($attachment, &$errors) {

        $attachmentid = NULL;

        // Check for an attachment
        if (isset($attachment) && isset($attachment['name']) && !empty($attachment['name'])) {

            // Get the list of valid attachment types and file extensions
            $attachmenttypes = $this->getAttachmentTypes($errors);

            // Construct an array containing only the 'extension' keys
            $extensions = array_column($attachmenttypes, 'extension');

            // Get the uploaded filename
            $filename = $attachment['name'];

            // Extract the uploaded file's extension
            $dot = strrpos($filename, ".");

            // Make sure the file has an extension and the last character of the name is not a "."
            if ($dot !== FALSE && $dot != strlen($filename)) {

                // Check to see if the uploaded file has an allowed file extension
                $extension = strtolower(substr($filename, $dot + 1));
                if (!in_array($extension, $extensions)) {

                    // Not a valid file extension
                    $errors[] = "File does not have a valid file extension";
                    $this->auditlog("saveAttachment", "invalid file extension: $filename");

                }

            } else {

                // No file extension -- Disallow
                $errors[] = "File does not have a valid file extension";
                $this->auditlog("saveAttachment", "no file extension: $filename");

            }

            // Only attempt to add the attachment to the database if the file extension was good
            if (sizeof($errors) == 0) {

                // Create a new ID
                $attachmentid = bin2hex(random_bytes(16));

                $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/saveAttachment";
          			$data = array(
          				'attachmentid'=>$attachmentid,
          				'filename'=>$filename
          			);
          			$data_json = json_encode($data);

          			$ch = curl_init();
          			curl_setopt($ch, CURLOPT_URL, $url);
          			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
          			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
          			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
          			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          			$response  = curl_exec($ch);
          			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

          			if ($response === FALSE) {
          				$errors[] = "An unexpected failure occurred contacting the web service.";
          			} else {

          				if($httpCode == 400) {

          					// JSON was double-encoded, so it needs to be double decoded
          					$errorsList = json_decode(json_decode($response))->errors;
          					foreach ($errorsList as $err) {
          						$errors[] = $err;
          					}
          					if (sizeof($errors) == 0) {
          						$errors[] = "Bad input";
          					}

          				} else if($httpCode == 500) {

          					$errorsList = json_decode(json_decode($response))->errors;
          					foreach ($errorsList as $err) {
          						$errors[] = $err;
          					}
          					if (sizeof($errors) == 0) {
          						$errors[] = "Server error";
                      $this->auditlog("saveAttachment", "failed => $response");
          					}

          				} else if($httpCode == 200) {

                    // Move the file from temp folder to html attachments folder
                    move_uploaded_file($attachment['tmp_name'], getcwd() . '/attachments/' . $attachmentid . '-' . $attachment['name']);
                    $attachmentname = $attachment["name"];
                    $this->auditlog("saveAttachment", "success: $attachmentname");


          				}

          			}


          			curl_close($ch);

            }

        }

        return $attachmentid;

    }

    // Adds a new thing to the database
    public function addThing($name, $attachment, &$errors) {

        // Get the user id from the session
        $user = $this->getSessionUser($errors);
        $userid = $user["userid"];
        $registrationcode = $user["registrationcode"];

        // Validate the user input
        if (empty($userid)) {
            $errors[] = "Missing user ID. Not logged in?";
        }
        if (empty($name)) {
            $errors[] = "Missing thing name";
        }

        // Only try to insert the data into the database if there are no validation errors
        if (sizeof($errors) == 0) {


            $attachmentid = $this->saveAttachment($attachment, $errors);

            // Only try to insert the data into the database if the attachment successfully saved
            if (sizeof($errors) == 0) {

                // Create a new ID
                $thingid = bin2hex(random_bytes(16));

                $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/addThing";
          			$data = array(
          				'thingid'=>$thingid,
          				'thingname'=>$name,
          				'thinguserid'=>$userid,
          				'thingregistrationcode'=>$registrationcode,
                  'thingattachmentid'=>$attachmentid
          			);
          			$data_json = json_encode($data);

          			$ch = curl_init();
          			curl_setopt($ch, CURLOPT_URL, $url);
          			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
          			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
          			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
          			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          			$response  = curl_exec($ch);
          			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

          			if ($response === FALSE) {
          				$errors[] = "An unexpected failure occurred contacting the web service.";
          			} else {

          				if($httpCode == 400) {

          					// JSON was double-encoded, so it needs to be double decoded
          					$errorsList = json_decode(json_decode($response))->errors;
          					foreach ($errorsList as $err) {
          						$errors[] = $err;
          					}
          					if (sizeof($errors) == 0) {
          						$errors[] = "Bad input";
          					}

          				} else if($httpCode == 500) {

          					$errorsList = json_decode(json_decode($response))->errors;
          					foreach ($errorsList as $err) {
          						$errors[] = $err;
          					}
          					if (sizeof($errors) == 0) {
          						$errors[] = "Server error";
          					}

          				} else if($httpCode == 200) {

          				$this->auditlog("addthing", "success: $name, id = $thingid");

          				}

          			}


        } else {
            $this->auditlog("addthing validation error", $errors);
        }

        // Return TRUE if there are no errors, otherwise return FALSE
        if (sizeof($errors) == 0){
            return TRUE;
        } else {
            return FALSE;
        }
    }
  }

    // Adds a new comment to the database
    public function addComment($text, $thingid, $attachment, &$errors) {

//ISSUE: New Comment can only created and outputed if you add attachment.
        // Get the user id from the session
        $user = $this->getSessionUser($errors);
        $userid = $user["userid"];

        // Validate the user input
        if (empty($userid)) {
            $errors[] = "Missing user ID. Not logged in?";
        }
        if (empty($thingid)) {
            $errors[] = "Missing thing ID";
        }
        if (empty($text)) {
            $errors[] = "Missing comment text";
        }

        // Only try to insert the data into the database if there are no validation errors
        if (sizeof($errors) == 0) {

            // Connect to the database


            $attachmentid = $this->saveAttachment($attachment, $errors);

            // Only try to insert the data into the database if the attachment successfully saved
            if (sizeof($errors) == 0) {

                // Create a new ID
                $commentid = bin2hex(random_bytes(16));

                // Add a record to the Comments table
                // Construct a SQL statement to perform the insert operation
                $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/addComment";
          			$data = array(
          				'commentid'=>$commentid,
          				'commenttext'=>$text,
          				'commentuserid'=>$userid,
          				'commentthingid'=>$thingid,
                  'commentattachmentid'=>$attachmentid
          			);
          			$data_json = json_encode($data);

          			$ch = curl_init();
          			curl_setopt($ch, CURLOPT_URL, $url);
          			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
          			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
          			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
          			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          			$response  = curl_exec($ch);
          			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $scan = json_decode($response);
          			if ($response === FALSE) {
          				$errors[] = "An unexpected failure occurred contacting the web service.";
          			} else {

          				if($httpCode == 400) {

          					// JSON was double-encoded, so it needs to be double decoded
          					$errorsList = json_decode(json_decode($response))->errors;
          					foreach ($errorsList as $err) {
          						$errors[] = $err;
          					}
          					if (sizeof($errors) == 0) {
          						  $errors[] = "An unexpected error occurred saving the comment to the database.";
          					}

          				} else if($httpCode == 500) {

          					$errorsList = json_decode(json_decode($response))->errors;
          					foreach ($errorsList as $err) {
          						$errors[] = $err;
          					}
          					if (sizeof($errors) == 0) {
          						$errors[] = "Server error";
                      print_r($scan);
          					}

          				} else if($httpCode == 200) {

          					$this->auditlog("addcomment", "success: $commentid");

          				}

          			}


          			curl_close($ch);

        } else {
            $this->auditlog("addcomment validation error", $errors);
        }

        // Return TRUE if there are no errors, otherwise return FALSE
        if (sizeof($errors) == 0){
            return TRUE;
        } else {
            return FALSE;
        }
    }
  }

    // Get a list of users from the database and will return the $errors array listing any errors encountered
    public function getUsers(&$errors) {

        // Assume an empty list of topics
        $users = array();

        $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/getUsers";

     		$ch = curl_init();
     		curl_setopt($ch, CURLOPT_URL, $url);
         curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
     		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
     		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
     		$response  = curl_exec($ch);
     		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         $scan = json_decode($response);

     		if ($response === FALSE) {
     			$errors[] = "An unexpected failure occurred contacting the web service.";
     		} else {

     			if($httpCode == 400) {

     				// JSON was double-encoded, so it needs to be double decoded
     				$errorsList = json_decode(json_decode($response))->errors;
     				foreach ($errorsList as $err) {
     					$errors[] = $err;
     				}
     				if (sizeof($errors) == 0) {
     					$errors[] = "Bad input";
               $this->auditlog("getUsers", "failed => $response");
     				}

     			} else if($httpCode == 500) {

     				$errorsList = json_decode(json_decode($response))->errors;
     				foreach ($errorsList as $err) {
     					$errors[] = $err;
     				}
     				if (sizeof($errors) == 0) {
     					$errors[] = "Server error";
                $this->auditlog("getUsers", "failed => $response");
     				}

     			} else if($httpCode == 200) {

             $this->auditlog("getUsers", "success");

             $scatter = $scan->users;
             foreach($scatter as $scat){
               $scat = array(
                 'userid'=>$scat->userid,
                 'username'=>$scat->username,
                 'email'=>$scat->email,
                 'isadmin'=>$scat->isadmin
               );
               $users[] = $scat;

             }

     			}

     		}

     		curl_close($ch);

        // Return the list of users
        return $users;

    }

    // Gets a single user from database and will return the $errors array listing any errors encountered
    public function getUser($userid, &$errors) {

        // Assume no user exists for this user id
        $user = NULL;

        // Validate the user input
        if (empty($userid)) {
            $errors[] = "Missing userid";
        }

        if(sizeof($errors)== 0) {

          $user = $this->getSessionUser($errors);
          $loggedinuserid = $user["userid"];




            // Check to see if the user really is logged in and really is an admin
            if ($loggedinuserid !== NULL) {
                $isadmin = $this->isAdmin($errors, $loggedinuserid);
            }

            // Stop people from viewing someone else's profile
            if (!$isadmin && $loggedinuserid !== $userid) {

                $errors[] = "Cannot view other user";
                $this->auditlog("getuser", "attempt to view other user: $loggedinuserid");

            } else {

                // Only try to insert the data into the database if there are no validation errors
                if (sizeof($errors) == 0) {

                  $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/getUser?userid=$userid";

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response  = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $scan = json_decode($response);


                    if ($response === FALSE) {
                      $errors[] = "An unexpected failure occurred contacting the web service.";
                    } else {

                      if($httpCode == 400) {

                        // JSON was double-encoded, so it needs to be double decoded
                        $errorsList = json_decode(json_decode($response))->errors;
                        foreach ($errorsList as $err) {
                          $errors[] = $err;
                        }
                        if (sizeof($errors) == 0) {
                          $errors[] = "Bad input";
                          $this->auditlog("getuser", "bad userid: $userid");
                        }

                      } else if($httpCode == 500) {

                        $errorsList = json_decode(json_decode($response))->errors;
                        foreach ($errorsList as $err) {
                          $errors[] = $err;
                        }
                        if (sizeof($errors) == 0) {
                          $errors[] = "Server error";
                           $this->auditlog("getUser", "failed => $response");
                        }

                      } else if($httpCode == 200) {

                        $this->auditlog("getUser", "success: $userid");

                        $user = array(
                              'userid'=>$scan->userid,
                              'username'=>$scan->username,
                              'email' => $scan->email,
                              'isadmin' => $scan->isadmin
                            );
                      }

                    }

                    curl_close($ch);

                } else {
                    $this->auditlog("getuser validation error", $errors);
                }
            }
        } else {
            $this->auditlog("getuser validation error", $errors);
        }

        // Return user if there are no errors, otherwise return NULL
        return $user;
    }

    // Updates a single user in the database and will return the $errors array listing any errors encountered
    public function updateUser($userid, $username, $email, $password, $isadminDB, &$errors) {

        // Assume no user exists for this user id
        $user = NULL;

        // Validate the user input
        if (empty($userid)) {

            $errors[] = "Missing userid";

        }

        if(sizeof($errors) == 0) {

            // Get the user id from the session
            $user = $this->getSessionUser($errors);
            $loggedinuserid = $user["userid"];
            $isadmin = FALSE;

            // Check to see if the user really is logged in and really is an admin
            if ($loggedinuserid != NULL) {
                $isadmin = $this->isAdmin($errors, $loggedinuserid);
            }

            // Stop people from editing someone else's profile
            if (!$isadmin && $loggedinuserid != $userid) {

                $errors[] = "Cannot edit other user";
                $this->auditlog("getuser", "attempt to update other user: $loggedinuserid");

            } else {

                // Validate the user input
                if (empty($userid)) {
                    $errors[] = "Missing userid";
                }
                if (empty($username)) {
                    $errors[] = "Missing username";
                }
                if (empty($email)) {
                    $errors[] = "Missing email;";
                }

                // Only try to update the data into the database if there are no validation errors
                if (sizeof($errors) == 0) {

                    // Hash the user's password
                    if(!empty($password)){
                    $passwordhash = password_hash($password, PASSWORD_DEFAULT);
                  }

                  if($loggedinuserid != $userid){
                    $isadminFlag = ($isadminDB ? "1" : "0");
                  }

                    $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/updateUser";
                    $data = array(
                      'userid'=>$userid,
                      'username'=>$username,
                      'passwordhash'=>$passwordhash,
                      'email'=>$email,
                      'isadmin'=>$isadminFlag
                    );
                    $data_json = json_encode($data);

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response  = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                    if ($response === FALSE) {
                      $errors[] = "An unexpected failure occurred contacting the web service.";
                    } else {

                      if($httpCode == 400) {

                        // JSON was double-encoded, so it needs to be double decoded
                        $errorsList = json_decode(json_decode($response))->errors;
                        foreach ($errorsList as $err) {
                          $errors[] = $err;
                        }
                        if (sizeof($errors) == 0) {
                          $errors[] = "Bad input";
                        }

                      } else if($httpCode == 500) {

                        $errorsList = json_decode(json_decode($response))->errors;
                        foreach ($errorsList as $err) {
                          $errors[] = $err;
                        }
                        if (sizeof($errors) == 0) {
                          $errors[] = "Server error";
                        }

                      } else if($httpCode == 200) {

                         $this->auditlog("updateUser", "success");

                      }

                    }


                    curl_close($ch);

                } else {
                    $this->auditlog("updateUser validation error", $errors);
                }
            }
        } else {
            $this->auditlog("updateUser validation error", $errors);
        }

        // Return TRUE if there are no errors, otherwise return FALSE
        if (sizeof($errors) == 0){
            return TRUE;
        } else {
            return FALSE;
        }
    }

    // Validates a provided username or email address and sends a password reset email
    public function passwordReset($usernameOrEmail, &$errors) {

        // Check for a valid username/email
        if (empty($usernameOrEmail)) {
            $errors[] = "Missing username/email";
            $this->auditlog("session", "missing username");
        }

        // Only proceed if there are no validation errors
        if (sizeof($errors) == 0) {

          $passwordresetid = bin2hex(random_bytes(16));

          $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/passwordReset";
          $data = array(
            'username'=>$usernameOrEmail,
            'email'=>$usernameOrEmail,
            'passwordresetid'=>$passwordresetid
          );
          $data_json = json_encode($data);

          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
          curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $response  = curl_exec($ch);
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

          $scan = json_decode($response);
          $userid = $scan->userid;
          $email = $scan->email;

          if ($response === FALSE) {
            $errors[] = "An unexpected failure occurred contacting the web service.";
          } else {

            if($httpCode == 400) {

              // JSON was double-encoded, so it needs to be double decoded
              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Bad input";
                $this->auditlog("passwordReset", "Bad request for $usernameOrEmail");
              }

            } else if($httpCode == 500) {

              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Server error";
              }

            } else if($httpCode == 200) {

              $this->auditlog("passwordReset", "Sending message to $email");

              // Send reset email
              $pageLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
              $pageLink = str_replace("reset.php", "password.php", $pageLink);
              $to      = $email;
              $subject = 'Password reset';
              $message = "A password reset request for this account has been submitted at http://52.4.120.179/it5236/website/. ".
                  "If you did not make this request, please ignore this message. No other action is necessary. ".
                  "To reset your password, please click the following link: $pageLink?id=$passwordresetid";
              $headers = 'From: developer@photofolio.com' . "\r\n" .
                  'Reply-To: sp05337@georgiasouthern.edu' . "\r\n";

              mail($to, $subject, $message, $headers);

              $this->auditlog("passwordReset", "Message sent to $email");


            }

          }

        }

    }

    // Validates a provided username or email address and sends a password reset email
    public function updatePassword($password, $passwordresetid, &$errors) {

        // Check for a valid username/email
        $this->validatePassword($password, $errors);
        if (empty($passwordresetid)) {
            $errors[] = "Missing passwordrequestid";
        }

        // Only proceed if there are no validation errors
        if (sizeof($errors) == 0) {

          $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/updatePassword";
          $data = array(
            'passwordresetid'=>$passwordresetid
          );
          $data_json = json_encode($data);

          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
          curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $response  = curl_exec($ch);
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          $scan = json_decode($response);
          $userid = $scan->userid;
          if ($response === FALSE) {
            $errors[] = "An unexpected failure occurred contacting the web service.";
          } else {

            if($httpCode == 400) {

              // JSON was double-encoded, so it needs to be double decoded
              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Bad input";
                $this->auditlog("updatePassword", "Bad request id: $passwordresetid");
              }

            } else if($httpCode == 500) {

              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Server error";
              }

            } else if($httpCode == 200) {


              $this->updateUserPassword($userid, $password, $errors);
              $this->clearPasswordResetRecords($passwordresetid);

            }

          }

        }

    }

    function getFile($name){
        return file_get_contents($name);
    }

    // Get a list of users from the database and will return the $errors array listing any errors encountered
    public function getAttachmentTypes(&$errors) {

        // Assume an empty list of topics
        $types = array();

        $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/getAttachmentTypes";

          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $response  = curl_exec($ch);
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          $scan = json_decode($response);


          if ($response === FALSE) {
            $errors[] = "An unexpected failure occurred contacting the web service.";
          } else {

            if($httpCode == 400) {

              // JSON was double-encoded, so it needs to be double decoded
              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Bad input";

              }

            } else if($httpCode == 500) {

              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Server error";
                 $this->auditlog("getAttachmentTypes", "failed => $response");
              }

            } else if($httpCode == 200) {

                $this->auditlog("getattachmenttypes", "success");
                foreach($scan as $scat){

                  $scat = array(
                        'attachmenttypeid'=>$scat->attachmenttypeid,
                        'name'=>$scat->name,
                        'extension' =>$scat->extension

                      );
                      $types[] = $scat;
                }
            }

          }

          curl_close($ch);


        // Return the list of users
        return $types;

    }

    // Creates a new session in the database for the specified user
    public function newAttachmentType($name, $extension, &$errors) {

        $attachmenttypeid = NULL;

        // Check for a valid name
        if (empty($name)) {
            $errors[] = "Missing name";
        }
        // Check for a valid extension
        if (empty($extension)) {
            $errors[] = "Missing extension";
        }

        // Only try to query the data into the database if there are no validation errors
        if (sizeof($errors) == 0) {

            // Create a new session ID
            $attachmenttypeid = bin2hex(random_bytes(25));


            $url = "https://zc4gdy8009.execute-api.us-east-1.amazonaws.com/default/newAttachmentType";
            $data = array(
              'attachmenttypeid'=>$attachmenttypeid,
              'name'=>$name,
              'extension'=>$extension
            );
            $data_json = json_encode($data);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: l2XyxFZ2O9kK82SLfjk72Iu4U0meetH4b50nbhc9','Content-Length: ' . strlen($data_json)));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response  = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === FALSE) {
              $errors[] = "An unexpected failure occurred contacting the web service.";
            } else {

              if($httpCode == 400) {

                // JSON was double-encoded, so it needs to be double decoded
                $errorsList = json_decode(json_decode($response))->errors;
                foreach ($errorsList as $err) {
                  $errors[] = $err;
                }
                if (sizeof($errors) == 0) {
                  $errors[] = "Bad input";

                }

              } else if($httpCode == 500) {

                $errorsList = json_decode(json_decode($response))->errors;
                foreach ($errorsList as $err) {
                  $errors[] = $err;
                }
                if (sizeof($errors) == 0) {
                  $errors[] = "Server error";

                }

              } else if($httpCode == 200) {



              }

            }


            curl_close($ch);

        } else {

            $this->auditlog("newAttachmentType error", $errors);
            return NULL;

        }

        return $attachmenttypeid;
    }

}


?>
