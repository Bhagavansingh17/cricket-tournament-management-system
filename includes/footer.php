</div> <!-- .container -->

    <!-- NEW FOOTER SECTION -->
    <footer class="main-footer">
        <p>&copy; <?php echo date("Y"); ?> CTMS Project. All Rights Reserved. (Hackathon)</p>
    </footer>

</body>
</html>
<?php
// Close the database connection
if (isset($conn)) {
    $conn->close();
}
?>