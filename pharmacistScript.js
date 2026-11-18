document.addEventListener("DOMContentLoaded", function () {

    const API = "pharmacist.php";


    /* =====================================================
       API REQUEST
    ===================================================== */

    async function request(action, data = {}) {

        const formData = new FormData();

        formData.append("action", action);

        Object.keys(data).forEach(function (key) {
            formData.append(key, data[key]);
        });

        try {

            const response = await fetch(API, {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            });

            const result = await response.json();

            if (!result.success) {
                alert(result.message || "Something went wrong.");
            }

            return result;

        } catch (error) {

            console.error(error);

            alert(
                "Unable to connect to the PHP server.\n" +
                "Please make sure WAMP Apache and MySQL are running."
            );

            return {
                success: false,
                data: []
            };
        }
    }


    /* =====================================================
       SIDEBAR
    ===================================================== */

    const navLinks =
        document.querySelectorAll(".nav-link[data-target]");

    navLinks.forEach(function (link) {

        link.addEventListener("click", function (event) {

            event.preventDefault();

            const targetId = link.dataset.target;
            const target = document.getElementById(targetId);

            if (target) {

                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });
            }

            navLinks.forEach(function (item) {
                item.classList.remove("active");
            });

            link.classList.add("active");
        });
    });


    /* =====================================================
       SIDEBAR TOGGLE
    ===================================================== */

    const sidebarToggle =
        document.getElementById("sidebarToggle");

    const sidebar =
        document.getElementById("sidebar");

    if (sidebarToggle && sidebar) {

        sidebarToggle.addEventListener("click", function () {

            sidebar.classList.toggle("show");

        });
    }


    /* =====================================================
       DASHBOARD
    ===================================================== */

    async function loadDashboard() {

        const result = await request("dashboard");

        if (!result.success) {
            return;
        }

        const data = result.data;

        const values =
            document.querySelectorAll(".stat-value");

        if (values.length >= 4) {

            values[0].textContent =
                data.pending_orders;

            values[1].textContent =
                data.low_stock;

            values[2].textContent =
                data.today_orders;

            values[3].textContent =
                data.total_medicines;
        }
    }


    /* =====================================================
       ORDER STATUS
       Pending / Approved / Rejected
    ===================================================== */

    async function loadOrderStatus() {

        const result =
            await request("order_status");

        if (!result.success) {
            return;
        }

        const data = result.data;


        /* ---------------------------------------------
           Order count elements
        --------------------------------------------- */

        const pending =
            document.getElementById("pendingOrders");

        const approved =
            document.getElementById("approvedOrders");

        const rejected =
            document.getElementById("rejectedOrders");


        /* ---------------------------------------------
           Display Pending count
        --------------------------------------------- */

        if (pending) {
            pending.textContent =
                data.pending || 0;
        }


        /* ---------------------------------------------
           Display Approved count
        --------------------------------------------- */

        if (approved) {
            approved.textContent =
                data.approved || 0;
        }


        /* ---------------------------------------------
           Display Rejected count
        --------------------------------------------- */

        if (rejected) {
            rejected.textContent =
                data.rejected || 0;
        }


        /* ---------------------------------------------
           Progress bars
        --------------------------------------------- */

        const pendingBar =
            document.getElementById("pendingBar");

        const approvedBar =
            document.getElementById("approvedBar");

        const rejectedBar =
            document.getElementById("rejectedBar");


        if (pendingBar) {

            pendingBar.style.width =
                (data.pending_percent || 0) + "%";
        }


        if (approvedBar) {

            approvedBar.style.width =
                (data.approved_percent || 0) + "%";
        }


        if (rejectedBar) {

            rejectedBar.style.width =
                (data.rejected_percent || 0) + "%";
        }
    }


    /* =====================================================
       MEDICINES
    ===================================================== */

    async function loadMedicines() {

        const result =
            await request("medicines");

        if (!result.success) {
            return;
        }

        const tbody =
            document.querySelector(
                ".medicine-table tbody"
            );

        if (!tbody) {
            return;
        }

        tbody.innerHTML = "";

        result.data.forEach(function (medicine) {

            const row =
                document.createElement("tr");

            row.dataset.id =
                medicine.medicine_id;

            row.innerHTML = `

                <td>
                    ${escapeHTML(
                        medicine.medicine_name
                    )}
                </td>

                <td>
                    ${escapeHTML(
                        medicine.category_name || ""
                    )}
                </td>

                <td>
                    ${medicine.stock_quantity}
                </td>

                <td>
                    ${formatDate(
                        medicine.expiry_date
                    )}
                </td>

                <td>
                    <button
                        class="edit-btn"
                        data-id="${medicine.medicine_id}">
                        Edit
                    </button>
                </td>

                <td>
                    <button
                        class="deactivate-btn delete-medicine"
                        data-id="${medicine.medicine_id}">
                        Delete
                    </button>
                </td>

            `;

            tbody.appendChild(row);
        });
    }


    /* =====================================================
       INLINE ADD MEDICINE FORM
    ===================================================== */

    const addMedicineButton = document.querySelector(".add-btn[data-modal='addMedicineModal']");
    const addMedicineForm = document.getElementById("addMedicineForm");
    const addMedicineBox = document.getElementById("addMedicineModal");

    if (addMedicineButton && addMedicineBox) {
        addMedicineButton.addEventListener("click", function (event) {
            event.preventDefault();
            addMedicineBox.hidden = false;
            addMedicineBox.scrollIntoView({ behavior: "smooth", block: "center" });
            const first = addMedicineForm && addMedicineForm.querySelector("input");
            if (first) setTimeout(() => first.focus(), 250);
        });
    }

    document.addEventListener("click", function (event) {
        const button = event.target.closest("[data-close-form='addMedicineModal']");
        if (button && addMedicineBox) addMedicineBox.hidden = true;
    });

    if (addMedicineForm) {
        addMedicineForm.addEventListener("submit", async function (event) {
            event.preventDefault();
            const message = document.getElementById("addMedicineMessage");
            const data = Object.fromEntries(new FormData(this).entries());
            data.requires_prescription = data.requires_prescription ? 1 : 0;
            if (message) { message.textContent = "Saving..."; message.className = "form-message"; }
            const result = await request("add_medicine", data);
            if (message) {
                message.textContent = result.success ? result.message : (result.message || "Unable to save.");
                message.className = "form-message " + (result.success ? "success" : "error");
            }
            if (result.success) {
                this.reset();
                this.querySelector("[name='min_stock_level']").value = 10;
                await loadMedicines();
                await loadDashboard();
                await loadOrderStatus();
                await loadNotifications();
                await loadInventory();
                await loadLowStock();
            }
        });
    }


    /* =====================================================
       EDIT MEDICINE
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            if (
                !event.target.classList.contains(
                    "edit-btn"
                )
            ) {
                return;
            }


            const medicineId =
                event.target.dataset.id;


            const result =
                await request("medicines");


            if (!result.success) {
                return;
            }


            const medicine =
                result.data.find(
                    item =>
                        String(item.medicine_id) ===
                        String(medicineId)
                );


            if (!medicine) {

                alert("Medicine not found.");

                return;
            }


            const name =
                prompt(
                    "Medicine name:",
                    medicine.medicine_name
                );


            if (!name) {
                return;
            }


            const dosage =
                prompt(
                    "Dosage:",
                    medicine.dosage || ""
                );


            const price =
                prompt(
                    "Price:",
                    medicine.price
                );


            const stock =
                prompt(
                    "Stock quantity:",
                    medicine.stock_quantity
                );


            const minStock =
                prompt(
                    "Minimum stock:",
                    medicine.min_stock_level
                );


            const expiry =
                prompt(
                    "Expiry date:",
                    medicine.expiry_date
                );


            const resultUpdate =
                await request(
                    "update_medicine",
                    {
                        medicine_id: medicineId,
                        medicine_name: name,
                        category_id:
                            medicine.category_id,
                        supplier_id:
                            medicine.supplier_id,
                        dosage: dosage || "",
                        price: price,
                        stock_quantity: stock,
                        min_stock_level: minStock,
                        expiry_date: expiry,
                        requires_prescription:
                            medicine.requires_prescription
                    }
                );


            if (resultUpdate.success) {

                alert(
                    "Medicine updated successfully."
                );

                await loadMedicines();
                await loadDashboard();
                await loadOrderStatus();
                await loadNotifications();
                await loadInventory();
                await loadLowStock();
            }

        }
    );


    /* =====================================================
       DELETE MEDICINE
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            if (
                !event.target.classList.contains(
                    "delete-medicine"
                )
            ) {
                return;
            }


            const medicineId =
                event.target.dataset.id;


            const confirmDelete =
                confirm(
                    "Are you sure you want to delete this medicine?"
                );


            if (!confirmDelete) {
                return;
            }


            const result =
                await request(
                    "delete_medicine",
                    {
                        medicine_id: medicineId
                    }
                );


            if (result.success) {

                alert(
                    "Medicine deleted successfully."
                );

                await loadMedicines();
                await loadDashboard();
                await loadOrderStatus();
                await loadNotifications();
                await loadInventory();
                await loadLowStock();
            }

        }
    );


    /* =====================================================
       ORDERS
    ===================================================== */

    async function loadOrders() {

        const result =
            await request("orders");

        if (!result.success) {
            return;
        }


        const tbody =
            document.querySelector(
                ".pending-orders-table tbody"
            );


        const recentTbody =
            document.querySelector(
                ".recent-orders-table tbody"
            );


        if (tbody) {

            tbody.innerHTML = "";


            result.data
                .filter(
                    order =>
                        order.status === "Pending"
                )
                .forEach(function (order) {

                    const row =
                        document.createElement("tr");


                    row.innerHTML = `

                        <td>
                            #${order.order_id}
                        </td>

                        <td>
                            #${order.patient_id}
                        </td>

                        <td>

                            ${escapeHTML(
                                order.prescription_id
                                    || "N/A"
                            )}

                            <button
                                class="view-order"
                                data-id="${order.order_id}">
                                [View]
                            </button>

                        </td>

                        <td>

                            <span class="status pending">
                                ${escapeHTML(
                                    order.status
                                )}
                            </span>

                        </td>

                        <td>

                            <button
                                class="approve-btn"
                                data-id="${order.order_id}">
                                Approve
                            </button>

                        </td>

                        <td>

                            <button
                                class="reject-btn"
                                data-id="${order.order_id}">
                                Reject
                            </button>

                        </td>

                    `;


                    tbody.appendChild(row);

                });
        }


        if (recentTbody) {

            recentTbody.innerHTML = "";


            result.data
                .slice(0, 10)
                .forEach(function (order) {

                    const row =
                        document.createElement("tr");


                    row.innerHTML = `

                        <td>
                            #${order.order_id}
                        </td>

                        <td>
                            ${escapeHTML(
                                order.patient
                            )}
                        </td>

                        <td>
                            ${escapeHTML(
                                order.medicines || "N/A"
                            )}
                        </td>

                        <td>

                            <span class="status ${getStatusClass(
                                order.status
                            )}">

                                ${escapeHTML(
                                    order.status === 'Ready'
                                        ? 'Ready to Pickup'
                                        : order.status
                                )}

                            </span>

                        </td>

                        <td>

                            <button
                                class="view-order"
                                data-id="${order.order_id}">
                                View
                            </button>

                            ${order.status === 'Approved' ? `
                                <button
                                    class="ready-btn"
                                    data-id="${order.order_id}">
                                    Ready for Pickup
                                </button>
                            ` : ''}

                        </td>

                    `;


                    recentTbody.appendChild(row);

                });
        }
    }


    /* =====================================================
       APPROVE ORDER
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            if (
                !event.target.classList.contains(
                    "approve-btn"
                )
            ) {
                return;
            }


            const orderId =
                event.target.dataset.id;


            const result =
                await request(
                    "approve_order",
                    {
                        order_id: orderId
                    }
                );


            if (result.success) {

                alert(
                    "Order approved successfully."
                );

                await loadOrders();
                await loadDashboard();
                await loadOrderStatus();
            }

        }
    );


    /* =====================================================
       REJECT ORDER
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            if (
                !event.target.classList.contains(
                    "reject-btn"
                )
            ) {
                return;
            }


            const orderId =
                event.target.dataset.id;


            const result =
                await request(
                    "reject_order",
                    {
                        order_id: orderId
                    }
                );


            if (result.success) {

                alert(
                    "Order rejected successfully."
                );

                await loadOrders();
                await loadDashboard();
                await loadOrderStatus();
            }

        }
    );


    /* =====================================================
       MARK ORDER READY FOR PICKUP
    ===================================================== */

    document.addEventListener(
        'click',
        async function (event) {

            if (!event.target.classList.contains('ready-btn')) {
                return;
            }

            const orderId = event.target.dataset.id;

            const result = await request(
                'ready_order',
                { order_id: orderId }
            );

            if (result.success) {
                alert('Order is ready for pickup.');
                await loadOrders();
                await loadDashboard();
                await loadOrderStatus();
            }
        }
    );


    /* =====================================================
       ORDER VIEW
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            if (
                !event.target.classList.contains(
                    "view-order"
                )
            ) {
                return;
            }


            const orderId =
                event.target.dataset.id;


            const result =
                await request(
                    "order_details",
                    {
                        order_id: orderId
                    }
                );


            if (!result.success) {
                return;
            }


            const order =
                result.data;


            let message =
                "Order ID: #" +
                order.order_id +
                "\n\n";


            message +=
                "Patient: " +
                order.patient +
                "\n";


            message +=
                "Email: " +
                order.email +
                "\n";


            message +=
                "Status: " +
                order.status +
                "\n\n";


            message +=
                "Medicines:\n";


            order.items.forEach(
                function (item) {

                    message +=
                        "- " +
                        item.medicine_name +
                        " x" +
                        item.quantity +
                        "\n";

                }
            );


            alert(message);
        }
    );


    /* =====================================================
       LOW STOCK
    ===================================================== */

    async function loadLowStock() {

        const result =
            await request("low_stock");

        if (!result.success) {
            return;
        }


        const tbody =
            document.querySelector(
                ".low-stock-table tbody"
            );


        if (!tbody) {
            return;
        }


        tbody.innerHTML = "";


        result.data.forEach(function (medicine) {

            const row =
                document.createElement("tr");


            row.innerHTML = `

                <td>
                    ${escapeHTML(
                        medicine.medicine_name
                    )}
                </td>

                <td>
                    ${medicine.stock_quantity}
                </td>

                <td>

                    <button
                        class="restock-btn"
                        data-id="${medicine.medicine_id}">

                        Restock

                    </button>

                </td>

            `;


            tbody.appendChild(row);

        });
    }


    /* =====================================================
       RESTOCK
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            if (
                !event.target.classList.contains(
                    "restock-btn"
                )
            ) {
                return;
            }


            const medicineId =
                event.target.dataset.id;


            const quantity =
                prompt(
                    "Enter quantity to restock:"
                );


            if (
                !quantity ||
                Number(quantity) <= 0
            ) {

                alert(
                    "Please enter a valid quantity."
                );

                return;
            }


            const result =
                await request(
                    "restock_request",
                    {
                        medicine_id: medicineId,
                        quantity: quantity
                    }
                );


            if (result.success) {

                alert(
                    "Restock request submitted successfully."
                );

                await loadNotifications();
            }

        }
    );


    /* =====================================================
       INVENTORY
    ===================================================== */

    async function loadInventory() {

        const result =
            await request("inventory");

        if (!result.success) {
            return;
        }


        const tbody =
            document.querySelector(
                ".inventory-table tbody"
            );


        if (!tbody) {
            return;
        }


        tbody.innerHTML = "";


        result.data.forEach(function (item) {

            const row =
                document.createElement("tr");


            row.innerHTML = `

                <td>
                    ${escapeHTML(
                        item.medicine_name
                    )}
                </td>

                <td>
                    ${item.stock_quantity}
                </td>

                <td>

                    <span class="status ${
                        item.low_stock
                            ? "inactive"
                            : "active"
                    }">

                        ${
                            item.low_stock
                                ? "Yes"
                                : "No"
                        }

                    </span>

                </td>

                <td>
                    ${formatDate(
                        item.expiry_date
                    )}
                </td>

                <td>

                    <span class="status ${
                        item.near_expiry
                            ? "inactive"
                            : "active"
                    }">

                        ${
                            item.near_expiry
                                ? "Yes"
                                : "No"
                        }

                    </span>

                </td>

                <td>

                    ${
                        escapeHTML(
                            item.supplier_name || "N/A"
                        )
                    }

                    <br>

                    ${
                        escapeHTML(
                            item.phone || ""
                        )
                    }

                </td>

            `;


            tbody.appendChild(row);

        });
    }


    /* =====================================================
       INQUIRIES
    ===================================================== */

    async function loadInquiries() {

        const result =
            await request("inquiries");

        if (!result.success) {
            return;
        }


        const tbody =
            document.querySelector(
                ".inquiries-table tbody"
            );


        if (!tbody) {
            return;
        }


        tbody.innerHTML = "";


        result.data.forEach(function (inquiry) {

            const row =
                document.createElement("tr");


            row.innerHTML = `

                <td>
                    ${escapeHTML(
                        inquiry.patient_name
                    )}
                </td>

                <td>
                    ${escapeHTML(
                        inquiry.subject
                    )}
                </td>

                <td class="message-cell">

                    ${escapeHTML(
                        inquiry.message
                    )}

                </td>

                <td>

                    <div class="reply-cell">

                        <input
                            type="text"
                            class="reply-input"
                            placeholder="Type a reply..."
                            data-id="${inquiry.inquiry_id}"
                        >

                        <button
                            class="reply-btn"
                            data-id="${inquiry.inquiry_id}">

                            Reply

                        </button>

                    </div>

                    ${
                        inquiry.reply
                            ? `<small>
                                Previous reply:
                                ${escapeHTML(
                                    inquiry.reply
                                )}
                               </small>`
                            : ""
                    }

                </td>

            `;


            tbody.appendChild(row);

        });
    }


    /* =====================================================
       REPLY INQUIRY
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            if (
                !event.target.classList.contains(
                    "reply-btn"
                )
            ) {
                return;
            }


            const inquiryId =
                event.target.dataset.id;


            const input =
                document.querySelector(
                    `.reply-input[data-id="${inquiryId}"]`
                );


            if (
                !input ||
                !input.value.trim()
            ) {

                alert(
                    "Please enter a reply."
                );

                return;
            }


            const result =
                await request(
                    "reply_inquiry",
                    {
                        inquiry_id: inquiryId,
                        reply: input.value.trim()
                    }
                );


            if (result.success) {

                alert(
                    "Reply sent successfully."
                );

                await loadInquiries();
            }

        }
    );


    /* =====================================================
       NOTIFICATIONS
    ===================================================== */

    async function loadNotifications() {

        const result =
            await request("notifications");

        if (!result.success) {
            return;
        }


        const tbody =
            document.querySelector(
                ".notifications-table tbody"
            );


        if (!tbody) {
            return;
        }


        tbody.innerHTML = "";


        result.data.forEach(function (notification) {

            const row =
                document.createElement("tr");


            const status =
                notification.admin_status ||
                "Pending";


            row.innerHTML = `

                <td>
                    ${escapeHTML(
                        notification.request_type
                    )}
                </td>

                <td>
                    ${escapeHTML(
                        notification.medicine_name || ""
                    )}
                </td>

                <td>
                    ${formatDateTime(
                        notification.submitted_date
                    )}
                </td>

                <td>

                    <span class="status ${
                        status.toLowerCase() === "approved"
                            ? "active"
                            : status.toLowerCase() === "rejected"
                            ? "inactive"
                            : "pending"
                    }">

                        ${escapeHTML(status)}

                    </span>

                </td>

                <td>

                    <button
                        class="view-notification"
                        data-id="${notification.notification_id}">

                        [View]

                    </button>

                </td>

            `;


            tbody.appendChild(row);

        });
    }


    /* =====================================================
       VIEW NOTIFICATION
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            if (
                !event.target.classList.contains(
                    "view-notification"
                )
            ) {
                return;
            }


            const notificationId =
                event.target.dataset.id;


            const result =
                await request("notifications");


            if (!result.success) {
                return;
            }


            const notification =
                result.data.find(
                    item =>
                        String(
                            item.notification_id
                        ) ===
                        String(notificationId)
                );


            if (!notification) {
                return;
            }


            alert(

                "Request Type: " +
                notification.request_type +

                "\nMedicine: " +
                notification.medicine_name +

                "\nStatus: " +
                notification.admin_status +

                "\nDetails: " +
                notification.details

            );
        }
    );


    /* =====================================================
       REPORT LINKS
    ===================================================== */

    const reportLinks = {

        sales:
            "report.php?role=pharmacist&type=sales&period=monthly",

        medicine:
            "report.php?role=pharmacist&type=medicine&period=monthly",

        inventory:
            "report.php?role=pharmacist&type=inventory&period=monthly"

    };


    const pdfLinks =
        document.querySelectorAll(".pdf-link");


    if (pdfLinks.length >= 3) {

        pdfLinks[0].href =
            reportLinks.sales;

        pdfLinks[1].href =
            reportLinks.medicine;

        pdfLinks[2].href =
            reportLinks.inventory;


        pdfLinks.forEach(function (link) {

            link.target = "_blank";

        });
    }


    /* =====================================================
       SEARCH MEDICINES
    ===================================================== */

    const searchInput =
        document.querySelector(
            ".medicine-search-input"
        );


    const searchButton =
        document.querySelector(
            ".search-btn"
        );


    if (searchButton) {

        searchButton.addEventListener(
            "click",
            async function () {

                const keyword =
                    searchInput
                        ? searchInput.value
                            .trim()
                            .toLowerCase()
                        : "";


                const result =
                    await request("medicines");


                if (!result.success) {
                    return;
                }


                const tbody =
                    document.querySelector(
                        ".medicine-table tbody"
                    );


                if (!tbody) {
                    return;
                }


                tbody.innerHTML = "";


                result.data
                    .filter(function (medicine) {

                        return (
                            keyword === "" ||
                            medicine.medicine_name
                                .toLowerCase()
                                .includes(keyword)
                        );

                    })
                    .forEach(function (medicine) {

                        const row =
                            document.createElement("tr");


                        row.innerHTML = `

                            <td>
                                ${escapeHTML(
                                    medicine.medicine_name
                                )}
                            </td>

                            <td>
                                ${escapeHTML(
                                    medicine.category_name || ""
                                )}
                            </td>

                            <td>
                                ${medicine.stock_quantity}
                            </td>

                            <td>
                                ${formatDate(
                                    medicine.expiry_date
                                )}
                            </td>

                            <td>

                                <button
                                    class="edit-btn"
                                    data-id="${medicine.medicine_id}">

                                    Edit

                                </button>

                            </td>

                            <td>

                                <button
                                    class="deactivate-btn delete-medicine"
                                    data-id="${medicine.medicine_id}">

                                    Delete

                                </button>

                            </td>

                        `;


                        tbody.appendChild(row);

                    });

            }
        );
    }


    /* =====================================================
       LOGOUT
    ===================================================== */

    document.querySelectorAll(
        ".logout-link, .header-link"
    ).forEach(function (link) {

        if (
            link.textContent
                .trim()
                .toLowerCase()
                .includes("logout")
        ) {

            link.addEventListener(
                "click",
                async function (event) {

                    event.preventDefault();


                    await request("logout");


                    window.location.href =
                        "login.php";

                }
            );
        }
    });


    /* =====================================================
       HELPER FUNCTIONS
    ===================================================== */

    function escapeHTML(value) {

        const div =
            document.createElement("div");

        div.textContent =
            value ?? "";

        return div.innerHTML;
    }


    function formatDate(date) {

        if (!date) {
            return "";
        }


        const parts =
            date.split("-");


        if (parts.length === 3) {

            return (
                parts[2] +
                "." +
                parts[1] +
                "." +
                parts[0]
            );
        }


        return date;
    }


    function formatDateTime(dateTime) {

        if (!dateTime) {
            return "";
        }


        const parts =
            dateTime.split(" ");


        if (parts.length >= 2) {

            return (
                formatDate(parts[0]) +
                " " +
                parts[1]
            );
        }


        return dateTime;
    }


    function getStatusClass(status) {

        status =
            String(status)
                .toLowerCase();


        if (status === "approved") {
            return "active";
        }


        if (status === "rejected") {
            return "inactive";
        }

        if (status === "ready" || status === "delivered") {
            return "active";
        }

        return "pending";
    }


    /* =====================================================
       INITIAL LOAD
    ===================================================== */

    loadDashboard();

    loadMedicines();

    loadOrders();

    loadOrderStatus();

    loadLowStock();

    loadInventory();

    loadInquiries();

    loadNotifications();

});