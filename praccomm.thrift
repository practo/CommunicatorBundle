namespace php praccomm
namespace py thrift

/*
 * Delivery status of the mails
 */
enum DeliveryStatus {
    DELIVERED = 1,
    OPENED = 2,
    BOUNCED = 3,
    FAILED = 4,
    NOVALIDEMAIL = 5,
    UNTRACKED = 6
}

/*
 * Type of email sent by the doctor
 */
enum DoctorEmailType {
    CANCELLATION = 1,
    CONFIRMATION = 2,
    ACCOUNT_INVITE = 3,
    ONLINE_APPOINTMENT = 4,
    REMINDER = 5,
    RESCHEDULE = 6,
    BILLS_REPORT = 7
}

struct Attachment {
    1: required string name;
    2: required string location;
}

/*
 * Doctor mail
 */
struct DoctorMail {
    1: required i64 doctor_mail_id;
    2: required i64 practice_id;
    3: required string from_name;
    4: required string from_address;
    5: required string to_address;
    6: optional list<string> cc;
    7: optional list<string> bcc;
    8: required string subject;
    9: required string body;
    10: required i64 date;
    11: required DoctorEmailType email_type;
    12: optional list<Attachment> attachments;
    13: optional i64 created_by;
    14: optional i64 created_on;
    15: optional i64 modified_by;
    16: optional i64 modified_on;
    17: optional DeliveryStatus delivery_status;
    18: optional string email_provider;
    19: optional string message_id;
    20: optional string smtp_reply;
    21: optional string open_time;
    22: optional string open_bounce_data;
    23: optional string unsubscribe_data;
    24: optional string spam_data;
    25: required string hash;
    26: optional string plaintext;
    27: optional string app_name;
    28: optional string to_name;
    29: optional string mail_type;
    30: optional i32 attempt;
    31: optional i64 doctor_profile_id;
    32: optional string reply_to;
}

struct DoctorEmails {
    1: required list<DoctorMail> mails;
}

struct PatientMail {
    1: required i64 patient_mail_id;
    2: required i64 patient_contact_id;
    3: required string from_name;
    4: required string from_address;
    5: required string to_address;
    6: optional list<string> cc;
    7: optional list<string> bcc;
    8: required string subject;
    9: required string body;
    10: required i64 date;
    11: required string email_type;
    12: optional list<Attachment> attachments;
    13: optional i64 created_by;
    14: optional i64 created_on;
    15: optional i64 modified_by;
    16: optional i64 modified_on;
    17: optional DeliveryStatus delivery_status;
    18: optional string email_provider;
    19: optional string message_id;
    20: optional string smtp_reply;
    21: optional string open_time;
    22: optional string open_bounce_data;
    23: optional string unsubscribe_data;
    24: optional string spam_data;
    25: required string hash;
    26: required i64 practice_id;
    27: optional string plaintext;
    28: optional string app_name;
    29: optional string to_name;
    30: optional string mail_type;
    31: optional i32 attempt;
    32: optional string reply_to;
}

struct PatientEmails {
    1: required list<PatientMail> mails;
}


enum StaffEmailType {
    ACCOUNT_MAILS = 1,
    EXPENSES_REPORT = 2,
    INCOME_REPORT = 3,
    ACCOUNT_INVITE = 4,
    ONLINE_APPOINTMENT = 5,
    REMINDER = 6,
    SUMMARY_FOLLOWUPBIRTHDAY = 7,
    ACCOUNT_DELETE = 8,
    BILLS_REPORT = 9,
    LOW_STOCK = 10,
    CREDIT_LOW = 11,
    CREDIT_ZERO = 12,
    V6_UPGRADE = 13,
    EXPIRING_BATCHES = 14
}


struct StaffMail {
    1: required i64 staff_mail_id;
    2: required string from_name;
    3: required string from_address;
    4: required string to_address;
    5: optional list<string> cc;
    6: optional list<string> bcc;
    7: required string subject;
    8: required string body;
    9: required i64 date;
    10: required StaffEmailType email_type;
    11: optional list<Attachment> attachments;
    12: optional i64 created_by;
    13: optional i64 created_on;
    14: optional i64 modified_by;
    15: optional i64 modified_on;
    17: optional DeliveryStatus delivery_status;
    18: optional string email_provider;
    19: optional string message_id;
    20: optional string smtp_reply;
    21: optional string open_time;
    22: optional string open_bounce_data;
    23: optional string unsubscribe_data;
    24: optional string spam_data;
    16: required string hash;
    25: required i64 practice_id;
    26: optional string plaintext;
    27: optional string app_name;
    28: optional string to_name;
    29: optional string mail_type;
    30: optional i64 staff_profile_id;
    31: optional i32 attempt;
    32: optional string reply_to;
}

struct StaffEmails {
    1: required list<StaffMail> mails;
}


enum DoctorSMSType {
    BULK = 1,
    CANCELLATION = 2,
    CONFIRMATION = 3,
    CUSTOM = 4,
    ONLINE_APPOINTMENT = 5,
    REMINDER = 6,
    RESCHEDULE = 7,
    SYSTEM = 8,
    PVR_APPOINTMENT = 9,
    PVR_CLINICDETAILS = 10,
    PVR_MISSED = 11,
    PVR_EMERGENCY = 12,
    PVR_VOICEMAIL = 13,
    PVR_CALL = 14
}

enum SMSDeliveryStatus {
    DELIVERED = 1,
    FAILED = 2,
    SENT = 3,
    WAITING = 4
}

enum CreditAction {
    ADD_PRACTO_CREDIT = 1,
    ADD_WALLET_CREDIT = 2,
    DEDUCT_PRACTO_CREDIT = 3,
    DEDUCT_WALLET_CREDIT = 4,
    INSERT_CREDIT = 5
}

struct DoctorSMS {
    1: required i64 doctor_sms_id;
    2: required i64 practice_id;
    3: required string name;
    4: required string mobile;
    5: required DoctorSMSType message_type;
    6: required string message_text;
    7: required i64 send_time;
    8: required i64 delivery_time;
    9: optional SMSDeliveryStatus status;
    10: optional string failure_reason;
    11: optional i64 attempt;
    12: optional string operator;
    13: optional string operator_reply;
    14: optional string operator_code;
    15: optional i64 created_by;
    16: optional i64 created_on;
    17: optional i64 modified_by;
    18: optional i64 modified_on;
    19: optional string app_name;
    20: optional string sms_type;
    21: optional i64 doctor_profile_id;
    22: optional string currency;
    23: optional double cost;
    24: optional string secondary_mobile;
    25: optional string expiry_time;
    26: optional string cost_deducted_from;
    27: optional string retry_for;
}

struct DoctorMessages {
    1: required list<DoctorSMS> messages;
}

struct PatientSMS {
    1: required i64 patient_sms_id;
    2: required i64 practice_id;
    3: required string name;
    4: required string mobile;
    5: required string message_type;
    6: required string message_text;
    7: required i64 send_time;
    8: required i64 delivery_time;
    9: optional SMSDeliveryStatus status;
    10: optional string failure_reason;
    11: optional i64 attempt;
    12: optional string operator;
    13: optional string operator_reply;
    14: required string operator_code;
    15: optional i64 created_by;
    16: optional i64 created_on;
    17: optional i64 modified_by;
    18: optional i64 modified_on;
    19: optional i64 patient_contact_id;
    20: optional string app_name;
    21: optional string sms_type;
    22: optional string currency;
    23: optional double cost;
    24: optional string secondary_mobile;
    25: optional string expiry_time;
    26: optional string cost_deducted_from;
    27: optional string retry_for;
}

struct PatientMessages {
    1: required list<PatientSMS> messages;
}

enum StaffSMSType {
    BULK = 1,
    CUSTOM = 2,
    ONLINE_APPOINTMENT = 3,
    REMINDER = 4,
    SYSTEM = 5,
    PVR_APPOINTMENT = 6,
    PVR_CLINICDETAILS = 7,
    PVR_MISSED = 8,
    PVR_EMERGENCY = 9,
    PVR_VOICEMAIL = 10,
    PVR_CALL = 11
}


struct StaffSMS {
    1: required i64 staff_sms_id;
    2: required i64 practice_id;
    3: required string name;
    4: required string mobile;
    5: required StaffSMSType message_type;
    6: required string message_text;
    7: required i64 send_time;
    8: required i64 delivery_time;
    9: optional SMSDeliveryStatus status;
    10: optional string failure_reason;
    11: optional i64 attempt;
    12: optional string operator;
    13: optional string operator_reply;
    14: required string operator_code;
    15: optional i64 created_by;
    16: optional i64 created_on;
    17: optional i64 modified_by;
    18: optional i64 modified_on;
    19: optional string app_name;
    20: optional string sms_type;
    21: optional i64 staff_profile_id;
    22: optional string currency;
    23: optional double cost;
    24: optional string secondary_mobile;
    25: optional string expiry_time;
    26: optional string cost_deducted_from;
    27: optional string retry_for;
}

struct StaffMessages {
    1: required list<StaffSMS> messages;
}

enum PersonType {
    PATIENT = 1,
    STAFF = 2,
    DOCTOR = 3
}

enum AppointmentSMSType {
    VERIFICATION = 1,
    REQUEST = 2,
    CONFIRMATION = 3,
    CANCELLATION = 4,
    RESCHEDULE = 5,
    TEST = 6,
    PASSED_ON = 7,
    WALK_IN = 8,
    PENDING = 9
}

struct AppointmentSMS {
    1: required i64 appointment_sms_id;
    2: required i64 appointment_id;
    3: required string name;
    4: required string mobile;
    5: required PersonType person_type;
    6: required string message_text;
    7: required AppointmentSMSType message_type;
    8: required i64 delivery_time;
    9: optional SMSDeliveryStatus status;
    10: optional string failure_reason;
    11: optional i64 attempt;
    12: optional string operator;
    13: optional string operator_reply;
    14: required string operator_code;
    15: optional i64 created_by;
    16: optional i64 created_on;
    17: optional i64 modified_by;
    18: optional i64 modified_on;
    19: optional string app_name;
    20: optional string currency;
    21: optional double cost;
    22: optional string expiry_time;
    23: optional string notes;
}

struct GenericSMS {
    1: required i64 id;
    2: required i64 reference_id;
    3: required string reference_type;
    4: required string name;
    5: required string mobile;
    6: required string message_text;
    7: required string message_type;
    8: required i64 delivery_time;
    9: optional SMSDeliveryStatus status;
    10: optional string failure_reason;
    11: optional i64 attempt;
    12: optional string operator;
    13: optional string operator_reply;
    14: required string operator_code;
    15: optional i64 created_by;
    16: optional i64 created_on;
    17: optional i64 modified_by;
    18: optional i64 modified_on;
    19: optional string app_name;
    20: optional string currency;
    21: optional double cost;
    22: optional string expiry_time;
    23: optional string notes;
}

enum GroupSMSType {
    INVITE = 1,
    NEW_EVENT = 2,
    EVENT_CANCELLATION = 3,
    EVENT_CONFIRMATION = 4
}

struct GroupSMS {
    1: required i64 group_sms_id;
    2: required i64 group_id;
    3: required string name;
    4: required string mobile;
    5: required i64 event_id;
    6: required string message_text;
    7: required GroupSMSType message_type;
    8: required i64 delivery_time;
    9: optional SMSDeliveryStatus status;
    10: optional string failure_reason;
    11: optional i64 attempt;
    12: optional string operator;
    13: optional string operator_reply;
    14: required string operator_code;
    15: optional i64 created_by;
    16: optional i64 created_on;
    17: optional i64 modified_by;
    18: optional i64 modified_on;
    19: optional string app_name;
    20: optional string currency;
    21: optional double cost;
    22: optional string expiry_time;
}

enum AppointmentMailType {
    VERIFICATION = 1,
    REQUEST = 2,
    CONFIRMATION = 3,
    CANCELLATION = 4,
    RESCHEDULE = 5,
    TEST = 6,
    PASSED_ON = 7,
    CUSTOM = 8,
    WALK_IN = 9,
    PENDING = 10
}

struct AppointmentMail {
    1: required i64 appointment_mail_id;
    2: required i64 appointment_id;
    3: required string from_name;
    4: required string from_address;
    5: required string to_address;
    6: optional list<string> cc;
    7: optional list<string> bcc;
    8: required string subject;
    9: required string body;
    10: required i64 date;
    11: required AppointmentMailType email_type;
    12: optional list<Attachment> attachments;
    13: optional i64 created_by;
    14: optional i64 created_on;
    15: optional i64 modified_by;
    16: optional i64 modified_on;
    17: optional DeliveryStatus delivery_status;
    18: optional string email_provider;
    19: optional string message_id;
    20: optional string smtp_reply;
    21: optional string open_time;
    22: optional string open_bounce_data;
    23: optional string unsubscribe_data;
    24: optional string spam_data;
    25: required string hash;
    27: optional string plaintext;
    28: optional string app_name;
    29: optional string to_name;
    30: optional string mail_type;
    31: optional i32 attempt;
    32: optional string reply_to;
}


enum GroupMailType {
    INVITE = 1,
    NEW_EVENT = 2,
    EVENT_CANCELLATION = 3,
    EVENT_CONFIRMATION = 4,
    EVENT_ATTENDANCE_CANCELLATION = 5,
    EVENT_ATTENDANCE_CONFIRMATION = 6
}

struct GroupMail {
    1: required i64 group_mail_id;
    2: required i64 group_id;
    3: required string from_name;
    4: required string from_address;
    5: required string to_address;
    6: optional list<string> cc;
    7: optional list<string> bcc;
    8: required string subject;
    9: required string body;
    10: required i64 date;
    11: required GroupMailType email_type;
    12: optional list<Attachment> attachments;
    13: optional i64 created_by;
    14: optional i64 created_on;
    15: optional i64 modified_by;
    16: optional i64 modified_on;
    17: optional DeliveryStatus delivery_status;
    18: optional string email_provider;
    19: optional string message_id;
    20: optional string smtp_reply;
    21: optional string open_time;
    22: optional string open_bounce_data;
    23: optional string unsubscribe_data;
    24: optional string spam_data;
    25: required string hash;
    27: optional string plaintext;
    28: optional string app_name;
    29: optional string to_name;
    30: optional string mail_type;
    31: optional i32 attempt;
    32: optional string reply_to;
    33: optional i64 event_id;
}


struct GenericMail {
    1: required i64 generic_mail_id;
    3: required string from_name;
    4: required string from_address;
    5: required string to_address;
    6: optional list<string> cc;
    7: optional list<string> bcc;
    8: required string subject;
    9: required string body;
    10: required i64 date;
    12: optional list<Attachment> attachments;
    11: required string email_type;
    13: optional i64 created_by;
    14: optional i64 created_on;
    15: optional i64 modified_by;
    16: optional i64 modified_on;
    17: optional DeliveryStatus delivery_status;
    18: optional string email_provider;
    19: optional string message_id;
    20: optional string smtp_reply;
    21: optional string open_time;
    22: optional string open_bounce_data;
    23: optional string unsubscribe_data;
    24: optional string spam_data;
    25: required string hash;
    27: optional string plaintext;
    28: optional string app_name;
    29: optional string to_name;
    30: optional string mail_type;
    31: optional i32 attempt;
    32: optional string reply_to;
    33: required i64 reference_id;
    34: required string reference_type;
}

enum HelloPlan {
    HELLO_LEGACY = 1,
    HELLO = 2
}

struct SmsCredit {
    1: required i64 practice_id;
    2: required double credit;
    3: required string currency;
    4: optional string created_by;
    6: optional string modified_by;
    7: optional CreditAction action;
    8: optional i32 attempt;
    9: optional string pricing_plan_id;
    10: optional HelloPlan hello_plan;
    11: optional double wallet_credit;
}

struct Cost {
    1: required double amount;
    2: required string currency;
    3: required string deducted_from;
}

enum Currency {
    INR = 1,
    USD = 2,
    SGD = 3
}

exception ApplicationException {
    1: required string message
}

service DoctorMailService {
    bool update(1:DoctorMail query);
    string search_by_practice_id(1:i64 pid, 2:string send_date);
    string get_mails_by_status(1:i64 pid, 2:string start_date, 3:string end_date);
    string get_mail_by_id(1:string doc_mail_id);
    string get_mails_by_application(1:string start_date, 2:string end_date);
    string get_mails_by_app_for_pid(1:i64 pid, 2:string start_date, 3:string end_date);
    bool send(1:DoctorMail doctor_mail);
}

service PatientMailService {
    bool update(1:PatientMail query);
    string search_by_practice_id(1:i64 pid, 2:string send_date);
    string get_mails_by_status(1:i64 pid, 2:string start_date, 3:string end_date);
    string get_mail_by_id(1:string patient_mail_id);
    string get_mails_by_contact_id(1:i64 pid, 2:string cid, 3:i64 start_date, 4:i64 end_date);
    string get_mails_by_application(1:string start_date, 2:string end_date);
    string get_mails_by_app_for_pid(1:i64 pid, 2:string start_date, 3:string end_date);
    string get_mails_by_app_for_cid(1:i64 pid, 2:string cid, 3:string start_date, 4:string end_date);
    bool send(1:PatientMail patient_mail);
}

service StaffMailService {
    bool update(1:StaffMail query);
    string search_by_practice_id(1:i64 pid, 2:string send_date);
    string get_mails_by_status(1:i64 pid, 2:string start_date, 3:string end_date);
    string get_mail_by_id(1:string staff_mail_id);
    string get_mails_by_application(1:string start_date, 2:string end_date);
    string get_mails_by_app_for_pid(1:i64 pid, 2:string start_date, 3:string end_date);
    bool send(1:StaffMail staff_mail);
}

service StaffSMSService {
    bool update(1:i64 sms_id, 2:StaffSMS doctor_sms);
    string search_by_practice_id(1:i64 pid, 2:string send_date, 3:list<string> sms_types);
    string get_messages_in_date_range(1:i64 pid, 2:string start_date, 3:string end_date);
    string get_messages_by_delivery_status(1:i64 pid, 2:string start_date, 3:string end_date, 4:list<string> sms_types, 5:list<string> status);
    string get_messages_by_failure_reason(1:i64 pid, 2:string start_date, 3:string end_date, 4:list<string> sms_types);
    string get_messages_by_application(1:string start_date, 2:string end_date);
    string get_messages_by_app_for_pid(1:i64 pid, 2:string start_date, 3:string end_date);
    string get_retried_messages_for_smsid(1:i64 sms_id);
    bool send(1:StaffSMS staff_sms);
}

service PatientSMSService {
    bool update(1:i64 sms_id, 2:PatientSMS doctor_sms);
    string search_by_practice_id(1:i64 pid, 2:string send_date, 3:list<string> sms_types);
    string get_messages_in_date_range(1:i64 pid, 2:string start_date, 3:string end_date);
    string get_messages_by_delivery_status(1:i64 pid, 2:string start_date, 3:string end_date, 4:list<string> sms_types, 5:list<string> status);
    string get_messages_by_failure_reason(1:i64 pid, 2:string start_date, 3:string end_date, 4:list<string> sms_types);
    string get_messages_by_contact_id(1:i64 pid, 2:string cid, 3:i64 start_date, 4:i64 end_date);
    string get_messages_by_application(1:string start_date, 2:string end_date);
    string get_messages_by_app_for_pid(1:i64 pid, 2:string start_date, 3:string end_date);
    string get_messages_by_app_for_cid(1:i64 pid, 2:string cid, 3:string start_date, 4:string end_date);
    string get_retried_messages_for_smsid(1:i64 sms_id);
    bool send(1:PatientSMS patient_sms);
}

service DoctorSMSService {
    bool update(1:i64 sms_id, 2:DoctorSMS doctor_sms);
    string search_by_practice_id(1:i64 pid, 2:string send_date, 3:list<string> sms_types);
    string get_messages_in_date_range(1:i64 pid, 2:string start_date, 3:string end_date);
    string get_messages_by_delivery_status(1:i64 pid, 2:string start_date, 3:string end_date, 4:list<string> sms_types, 5:list<string> status);
    string get_messages_by_failure_reason(1:i64 pid, 2:string start_date, 3:string end_date, 4:list<string> sms_types);
    string get_messages_by_application(1:string start_date, 2:string end_date);
    string get_messages_by_app_for_pid(1:i64 pid, 2:string start_date, 3:string end_date);
    string get_retried_messages_for_smsid(1:i64 sms_id);
    bool send(1:DoctorSMS doctor_sms);
}

service AppointmentSMSService {
    string get_messages_by_appointment_id(1:i64 appointment_id, 2:list<string> sms_types);
}

service GroupSMSService {
    string get_messages_by_group_id(1:i64 group_id, 2:list<string> sms_types);
    string get_messages_by_event_id(1:i64 group_id, 2:i64 event_id, 3:list<string> sms_types);
}

service AppointmentMailService {
    string get_mails_by_appointment_id(1:i64 appointment_id, 2:list<string> mail_types);
}

service GroupMailService {
    string get_mails_by_group_id(1:i64 group_id, 2:list<string> mail_types);
    string get_mails_by_event_id(1:i64 group_id, 2:i64 event_id, 3:list<string> mail_types);
}

service SmsCreditService {
    bool insert_credit(1:i64 pid, 2:double credit, 3:double user_credit, 4:Currency currency, 5:string plan_id, 6:HelloPlan hello_plan) throws (1: ApplicationException expt);
    bool reset_practo_credit(1:i64 practice_id);
    bool change_sms_plan(1:i64 practice_id, 2:string plan_id) throws (1: ApplicationException expt);
    bool change_hello_plan(1:i64 practice_id, 2:HelloPlan hello_plan) throws (1: ApplicationException expt);
    bool add_practo_credit(1:i64 pid, 2:double credit) throws (1: ApplicationException expt);
    bool add_wallet_credit(1:i64 pid, 2:double wallet_credit) throws (1: ApplicationException expt);
    Cost deduct_credit(1:i64 pid, 2:double amount) throws (1: ApplicationException expt);
    bool deduct_practo_credit(1:i64 pid, 2:double amount) throws (1: ApplicationException expt);
    bool deduct_wallet_credit(1:i64 pid, 2:double amount) throws (1: ApplicationException expt);
    Cost deduct_hello_call_cost(1:i64 pid, 2:i32 call_duration_mins) throws (1: ApplicationException expt);
    string get_credit(1:i64 practice_id) throws (1: ApplicationException expt);
    string get_credit_for_pid(1:i64 practice_id) throws (1: ApplicationException expt);
    string get_message_expense_report(1:i64 practice_id, 2:i64 from_date, 3:i64 to_date) throws (1: ApplicationException expt);
    bool set_unlimited_credit_flag(1:i64 practice_id) throws (1: ApplicationException expt);
    bool add_practice_to_credit_pool(1:i64 old_practice_id, 2:i64 new_practice_id) throws (1: ApplicationException expt);
}

service GenericSMSService {
    string get_messages_by_reference_id(1:i64 reference_id, 2:list<string> sms_types);
}
