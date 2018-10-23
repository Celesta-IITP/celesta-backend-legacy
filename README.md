# celesta-backend
## API details:  
### Compousloury API Key, send via post as 'apiKey'
/login   
* POST  
  *  emailid   
  *  password   
* JSON Response:  
{   
  status: //http status code   
  userID: //uid of logged in user in case of successful login    
  name : //name   
  college : //college   
  events: ['event1','event2'],   
  message: //message from the server   
}   
  
/register
* POST  
  *  name   
  *  emailid   
  *  password   
  *  mobile
  *  college
* JSON Response:  
{   
  status: //http status code   
  message: //message from the server    
}   
## QR
login with regular API:  
https://celesta.org.in/apiLe/login   
Request body:     
emailid:"CLST1234"   
password:"password"    
Response contains "val" key.    
use that key for subsequent requests.     


POST Request body is the same for the following API endpoints:     
val:"that key from login"     
uID:"4 digit celestaID of the admin/organizer"      
       
QR pairing with Celesta ID:       
POST: `qr/pair/{clstID}/{qrHash}`       
{clstID} is of the person being registered.       
{qrHash} is scanned qr that is to be paired to the ID.       
       
       
Indicating person is in campus       
`qr/checkin/{qrHash}`       
{qrHash} is the scanned qr of the person entering the campus.       
       
Indicating person is leaving campus.       
`qr/checkout/{qrHash}`       
{qrHash} is scanned qr of the person leaving the campus.       
       
Following APIs haven't been implemented but are under development:       
       
Get list of registered users for event {eventID}       
`qr/getReg/{eventID}/`       
       
Register user for event       
`qr/setReg/{eventID}/{qrHash}`       
       
Status code:  
  * 200 : successful   
  * 500 : DB connect error   
  * 409 : Duplicate entry for registration
  * 403 : unauthorised/invalid login   
  * 400 : bad request error   
  * description of error is in the "message of the JSOn object"  
  
