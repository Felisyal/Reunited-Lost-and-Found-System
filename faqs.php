<?php

$faqs = [

[
    "keywords" => ["claim","how claim","claim item"],
    "answer" =>
"To claim an item:

If your report status is **Ready for Claim**, it means the administrator has verified an AI-assisted match between your lost report and a found item.

1. Visit the PUP Parañaque Lost and Found Office during office hours.
2. Present your valid School ID for identity verification.
3. The administrator will compare your claim with the report details to confirm ownership.
4. Once your ownership is verified, the item will be released to you and its status will be updated to **Claimed**.

For security purposes, only the verified rightful owner is allowed to claim the item."
],

[
    "keywords" => [
        "report lost",
        "lost report",
        "submit lost",
        "how to report lost",
        "how do i report a lost item",
        "i lost my item",
        "lost my item",
        "report missing item",
        "add lost report",
        "lost item"
    ],
    "answer" => "Go to Report Lost Items and click Add Report. Fill in the item details, upload an image if available, and submit it for admin approval."
],

[
    "keywords" => [
        "report found",
        "submit found",
        "found item",
        "how to report found",
        "how do i report a found item",
        "i found an item",
        "found something",
        "add found report",
        "surrender found item",
        "found report"
    ],
    "answer" => "Open Report Found Items, enter the item information, upload a clear photo, and submit it. The administrator will verify the report before it becomes active."
],

[
    "keywords" => ["what is status","pending","approved","ready","claimed", "update", "claim"],
    "answer" =>
"Status meanings in Reunited Lost and Found System:

• Pending — Waiting for admin review.
• Approved — Report verified and active.
• Ready for Claim — A verified match has been found.
• Claimed — The item has already been released.
• Rejected —  Report needs revision and re-submit it."
],

[
    "keywords" => [
        "reunited",
        "what is reunited",
        "what is the reunited system",
        "reunited system",
        "what is this system",
        "about reunited",
        "about the system",
        "system"
    ],
    "answer" => "Reunited is an AI-assisted Lost and Found Management System developed for the Polytechnic University of the Philippines – Parañaque Campus. It provides a centralized platform where students and authorized staff can report lost or found items, track report status, and submit claims. The system also uses AI-assisted matching with administrator verification to help identify potential matches between lost and found items."
],

[
    "keywords" => ["ai matching", "assisted matching", "what is ai matching", "what is assisted matching"],
    "answer" => "AI Assisted Matching compares the descriptions of approved lost and found reports using OpenAI embeddings..."
],
[
    "keywords" => ['what information do i need to submit', 'information submit a report', 'submit report','details'],
    "answer" => 
    "To submit a lost or found item report in Reunited, please provide the following information:

Item Name – Enter the name of the lost or found item.

Category – Select the appropriate item category.

Location – Specify where the item was lost or found.

Date – Enter the date the item was lost or discovered.

Type – Choose whether the report is for a Lost or Found item.

Status – Select the current report status (if applicable for admin entries).

Description – Provide a detailed description, including color, brand, distinctive features, or other identifying details.

Actual Photo – Upload a clear photo of the item. For verification, the image should show the item being held by the person who lost or found it, with their face visible."
],
[
    "keywords" => ["who can use this", "use", "user", "user for this system"],
    "answer" => 
    "The Reunited System is designed exclusively for the Polytechnic University of the Philippines – Parañaque Campus community. It can be used by currently enrolled students and authorized PUP Parañaque staff.

1.Students can report lost or found items, track their reports, submit claims, and receive notifications.
2.Staff can report found items, monitor their submitted reports, and assist in returning items.
3.Administrators manage the system by verifying reports, reviewing AI-assisted matches, and approving or rejecting claims."
],
[
    "keywords" => [
        "how long will found items be kept",
        "how long are items kept",
        "how long do you keep found items",
        "retention period",
        "30 days",
        "keep found item",
        "item expiration",
        "expire item"
    ],
    "answer" => "Found items are kept in the Reunited System for **30 days** from the date they are reported. If the item is not claimed within this period, it may be archived or handled according to the Lost and Found Office's policy."
],
[
    "keywords" => [
        "why rejected",
        "report rejected",
        "why was my report rejected",
        "rejected report",
        "report declined",
        "rejection reason"
    ],
    "answer" => "Your report may be rejected if it does not meet the verification requirements. Common reasons include:\n\n• The uploaded photo appears to be a prank or unrelated to the reported item.\n• A found item photo does not clearly show the person holding the item with their face visible.\n• The location or room number does not exist within the PUP Parañaque campus.\n• The item details are incomplete, inconsistent, or do not appear to be genuine.\n\nIf your report is rejected, you may review the information, correct the details, and submit a new report."
],
[
    "keywords" => [
        "why did my item get matched",
        "why matched",
        "item matched",
        "matched item",
        "why is my item matched",
        "match reason",
        "why did ai match my item",
        "similarity",
        "matched by ai"
    ],
    "answer" => "Your item was matched because the AI found strong similarities between an approved lost report and a found report. The matching process compares the item name, description, category, and location to identify possible matches. The similarity percentage is only a recommendation, and the administrator must still verify and approve the match before the item becomes Ready for Claim."
],


];