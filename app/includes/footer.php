<?php $isMinimal = isset($layout) && $layout === 'minimal'; ?>

<?php if ($isMinimal): ?>
</div><!-- end minimal layout -->
<?php else: ?>
        </main>
    </div><!-- end main content -->
</div><!-- end app shell -->
<?php endif; ?>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}
</script>

</body>
</html>
