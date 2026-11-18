<?php

require_once __DIR__ . '/dashboard_common.php';

requireDashboardRole('Pharmacist');

require_once __DIR__ . '/dashboard_nav.php';


$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int) $_POST['inquiry_id'];
    $reply = trim($_POST['reply'] ?? '');

    if ($reply !== '') {

        $st = $conn->prepare(
            "UPDATE inquiries
             SET
                reply=?,
                replied_by=?,
                replied_at=NOW()
             WHERE inquiry_id=?"
        );

        $uid = (int) $_SESSION['user_id'];

        $st->bind_param(
            'sii',
            $reply,
            $uid,
            $id
        );

        $st->execute();
        $st->close();

        $msg = 'Reply saved.';
    }
}


$rows = $conn->query(
    "SELECT
        i.*,
        u.full_name
     FROM inquiries i
     JOIN users u
        ON u.user_id=i.patient_id
     ORDER BY i.created_at DESC"
);


renderPageStart(
    'Pharmacist',
    'pharmacist_inquiries.php',
    'Inquiries'
);

?>


<div class="welcome">

    <h2>
        Patient Inquiries
    </h2>

    <p>
        Respond to patient questions from one dedicated page.
    </p>

</div>


<?php if ($msg): ?>

    <div class="notice">
        <?= e($msg) ?>
    </div>

<?php endif; ?>


<div class="panel">

    <div class="table-wrap">

        <table class="table">

            <tr>

                <th>
                    Patient
                </th>

                <th>
                    Subject
                </th>

                <th>
                    Message
                </th>

                <th>
                    Reply
                </th>

            </tr>


            <?php while ($i = $rows->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?= e($i['full_name']) ?>
                    </td>


                    <td>
                        <?= e($i['subject']) ?>
                    </td>


                    <td>
                        <?= e($i['message']) ?>
                    </td>


                    <td>

                        <form method="post">

                            <input
                                type="hidden"
                                name="inquiry_id"
                                value="<?= $i['inquiry_id'] ?>"
                            >


                            <textarea
                                name="reply"
                                rows="2"
                                placeholder="Write a reply..."
                            ><?= e($i['reply'] ?? '') ?></textarea>


                            <button
                                class="btn"
                                type="submit"
                            >
                                Save Reply
                            </button>

                        </form>

                    </td>

                </tr>

            <?php endwhile; ?>

        </table>

    </div>

</div>


<?php

renderPageEnd();

?>