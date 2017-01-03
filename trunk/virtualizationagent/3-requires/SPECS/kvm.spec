%{!?kversion: %define kversion 2.6.18-274.7.1.el5}

%define bios_id seabios-1.6.3
%define pxe_rom_id ipxe-git-aaf7a35
%define vgabios_id vgabios-0.6c
# sgabios comes from: http://sgabios.googlecode.com/svn/trunk, Rev 8
%define sgabios_id sgabios

# note that this list doesn't include the ipxe roms since they get handled
# individually and not as a group
%define firmware_files {bios.bin sgabios.bin vgabios.bin vgabios-cirrus.bin vgabios-stdvga.bin vgabios-vmware.bin vgabios-qxl.bin optionrom/extboot.bin optionrom/linuxboot.bin optionrom/multiboot.bin optionrom/vapic.bin}

%ifarch %ix86 x86_64
# choice of building all from source or using provided binary x86 blobs
%define build_fw_from_source 1
%else
%define build_fw_from_source 0
%endif

%{!?remove_header_extension_to_large:%define remove_header_extension_to_large 0}

Summary: Kernel-based Virtual Machine
Name: kvm
Version: 1.2.2
Release: 2%{?dist}
License: GPL
Group: Development/Tools
URL: http://kvm.sf.net

# There aren't qemu-kvm 1.2 maint releases yet, so we are carrying patches
Source0: http://downloads.sourceforge.net/sourceforge/kvm/qemu-kvm-1.2.0.tar.gz

Source1: kvm.modules
Patch0: qemu-kvm.centos.patch

# Patches queued for 1.2.1 stable
Patch0001: 0001-target-xtensa-convert-host-errno-values-to-guest.patch
Patch0002: 0002-target-cris-Fix-buffer-overflow.patch
Patch0003: 0003-target-xtensa-fix-missing-errno-codes-for-mingw32.patch
Patch0004: 0004-target-sparc-fix-fcmp-s-d-q-instructions-wrt-excepti.patch
Patch0005: 0005-target-s390x-fix-style.patch
Patch0006: 0006-target-s390x-split-FPU-ops.patch
Patch0007: 0007-target-s390x-split-condition-code-helpers.patch
Patch0008: 0008-target-s390x-split-integer-helpers.patch
Patch0009: 0009-target-s390x-split-memory-access-helpers.patch
Patch0010: 0010-target-s390x-rename-op_helper.c-to-misc_helper.c.patch
Patch0011: 0011-target-s390x-avoid-AREG0-for-FPU-helpers.patch
Patch0012: 0012-target-s390x-avoid-AREG0-for-integer-helpers.patch
Patch0013: 0013-target-s390x-avoid-AREG0-for-condition-code-helpers.patch
Patch0014: 0014-target-s390x-avoid-AREG0-for-misc-helpers.patch
Patch0015: 0015-target-s390x-switch-to-AREG0-free-mode.patch
Patch0016: 0016-tcg-s390-fix-ld-st-with-CONFIG_TCG_PASS_AREG0.patch
Patch0017: 0017-target-arm-Fix-potential-buffer-overflow.patch
Patch0018: 0018-tcg-optimize-split-expression-simplification.patch
Patch0019: 0019-tcg-optimize-simplify-or-xor-r-a-0-cases.patch
Patch0020: 0020-tcg-optimize-simplify-and-r-a-0-cases.patch
Patch0021: 0021-tcg-optimize-simplify-shift-rot-r-0-a-movi-r-0-cases.patch
Patch0022: 0022-tcg-optimize-swap-brcond-setcond-arguments-when-poss.patch
Patch0023: 0023-tcg-optimize-add-constant-folding-for-setcond.patch
Patch0024: 0024-tcg-optimize-add-constant-folding-for-brcond.patch
Patch0025: 0025-tcg-optimize-fix-if-else-break-coding-style.patch
Patch0026: 0026-target-s390x-avoid-cpu_single_env.patch
Patch0027: 0027-target-lm32-switch-to-AREG0-free-mode.patch
Patch0028: 0028-target-m68k-switch-to-AREG0-free-mode.patch
Patch0029: 0029-target-m68k-avoid-using-cpu_single_env.patch
Patch0030: 0030-target-unicore32-switch-to-AREG0-free-mode.patch
Patch0031: 0031-target-arm-convert-void-helpers.patch
Patch0032: 0032-target-arm-convert-remaining-helpers.patch
Patch0033: 0033-target-arm-final-conversion-to-AREG0-free-mode.patch
Patch0034: 0034-target-microblaze-switch-to-AREG0-free-mode.patch
Patch0035: 0035-target-cris-Avoid-AREG0-for-helpers.patch
Patch0036: 0036-target-cris-Switch-to-AREG0-free-mode.patch
Patch0037: 0037-target-sh4-switch-to-AREG0-free-mode.patch
Patch0038: 0038-target-mips-switch-to-AREG0-free-mode.patch
Patch0039: 0039-Remove-unused-CONFIG_TCG_PASS_AREG0-and-dead-code.patch
Patch0040: 0040-tcg-i386-allow-constants-in-load-store-ops.patch
Patch0041: 0041-tcg-mark-set_label-with-TCG_OPF_BB_END-flag.patch
Patch0042: 0042-revert-TCG-fix-copy-propagation.patch
Patch0043: 0043-target-mips-Set-opn-in-gen_ldst_multiple.patch
Patch0044: 0044-target-mips-Fix-MIPS_DEBUG.patch
Patch0045: 0045-target-mips-Always-evaluate-debugging-macro-argument.patch
Patch0046: 0046-tcg-optimize-fix-end-of-basic-block-detection.patch
Patch0047: 0047-target-xtensa-fix-extui-shift-amount.patch
Patch0048: 0048-target-xtensa-don-t-emit-extra-tcg_gen_goto_tb.patch
Patch0049: 0049-tcg-Introduce-movcond.patch
Patch0050: 0050-target-alpha-Use-movcond.patch
Patch0051: 0051-tcg-i386-Implement-movcond.patch
Patch0052: 0052-tcg-Optimize-movcond-for-constant-comparisons.patch
Patch0053: 0053-tcg-Optimize-two-address-commutative-operations.patch
Patch0054: 0054-gdbstub-sh4-fix-build-with-USE_SOFTFLOAT_STRUCT_TYPE.patch
Patch0055: 0055-tcg-Fix-USE_DIRECT_JUMP.patch
Patch0056: 0056-tcg-hppa-Fix-brcond2-and-setcond2.patch
Patch0057: 0057-tcg-hppa-Fix-broken-load-store-helpers.patch
Patch0058: 0058-tcg-mips-fix-wrong-usage-of-Z-constraint.patch
Patch0059: 0059-tcg-mips-kill-warnings-in-user-mode.patch
Patch0060: 0060-tcg-mips-use-TCGArg-or-TCGReg-instead-of-int.patch
Patch0061: 0061-tcg-mips-don-t-use-global-pointer.patch
Patch0062: 0062-tcg-mips-use-stack-for-TCG-temps.patch
Patch0063: 0063-tcg-mips-optimize-brcond-arg-0.patch
Patch0064: 0064-tcg-mips-optimize-bswap-16-16s-32-on-MIPS32R2.patch
Patch0065: 0065-tcg-mips-implement-rotl-rotr-ops-on-MIPS32R2.patch
Patch0066: 0066-tcg-mips-implement-deposit-op-on-MIPS32R2.patch
Patch0067: 0067-tcg-mips-implement-movcond-op-on-MIPS32R2.patch
Patch0068: 0068-tcg-optimize-remove-TCG_TEMP_ANY.patch
Patch0069: 0069-tcg-optimize-check-types-in-copy-propagation.patch
Patch0070: 0070-tcg-optimize-rework-copy-progagation.patch
Patch0071: 0071-tcg-optimize-do-copy-propagation-for-all-operations.patch
Patch0072: 0072-tcg-optimize-optimize-op-r-a-a-mov-r-a.patch
Patch0073: 0073-tcg-optimize-optimize-op-r-a-a-movi-r-0.patch
Patch0074: 0074-tcg-optimize-further-optimize-brcond-movcond-setcond.patch
Patch0075: 0075-tcg-optimize-prefer-the-op-a-a-b-form-for-commutativ.patch
Patch0076: 0076-tcg-remove-ifdef-endif-around-TCGOpcode-tests.patch
Patch0077: 0077-tcg-optimize-add-constant-folding-for-deposit.patch
Patch0078: 0078-tcg-README-document-tcg_gen_goto_tb-restrictions.patch
Patch0079: 0079-w64-Fix-TCG-helper-functions-with-5-arguments.patch
Patch0080: 0080-tcg-ppc32-Implement-movcond32.patch
Patch0081: 0081-tcg-sparc-Hack-in-qemu_ld-st64-for-32-bit.patch
Patch0082: 0082-tcg-sparc-Fix-ADDX-opcode.patch
Patch0083: 0083-tcg-sparc-Don-t-MAP_FIXED-on-top-of-the-program.patch
Patch0084: 0084-tcg-sparc-Assume-v9-cpu-always-i.e.-force-v8plus-in-.patch
Patch0085: 0085-tcg-sparc-Fix-qemu_ld-st-to-handle-32-bit-host.patch
Patch0086: 0086-tcg-sparc-Support-GUEST_BASE.patch
Patch0087: 0087-tcg-sparc-Change-AREG0-in-generated-code-to-i0.patch
Patch0088: 0088-tcg-sparc-Clean-up-cruft-stemming-from-attempts-to-u.patch
Patch0089: 0089-tcg-sparc-Mask-shift-immediates-to-avoid-illegal-ins.patch
Patch0090: 0090-tcg-sparc-Use-defines-for-temporaries.patch
Patch0091: 0091-tcg-sparc-Add-g-o-registers-to-alloc_order.patch
Patch0092: 0092-tcg-sparc-Fix-and-enable-direct-TB-chaining.patch
Patch0093: 0093-tcg-sparc-Preserve-branch-destinations-during-retran.patch
Patch0094: 0094-target-alpha-Initialize-env-cpu_model_str.patch
Patch0095: 0095-tcg-mips-fix-MIPS32-R2-detection.patch
Patch0096: 0096-tcg-Adjust-descriptions-of-cond-opcodes.patch
Patch0097: 0097-tcg-i386-fix-build-with-march-i686.patch
Patch0098: 0098-tcg-Fix-MAX_OPC_PARAM_IARGS.patch
Patch0099: 0099-tci-Fix-for-AREG0-free-mode.patch
Patch0100: 0100-spice-abort-on-invalid-streaming-cmdline-params.patch
Patch0101: 0101-spice-notify-spice-server-on-vm-start-stop.patch
Patch0102: 0102-spice-notify-on-vm-state-change-only-via-spice_serve.patch
Patch0103: 0103-spice-migration-add-QEVENT_SPICE_MIGRATE_COMPLETED.patch
Patch0104: 0104-spice-add-migrated-flag-to-spice-info.patch
Patch0105: 0105-spice-adding-seamless-migration-option-to-the-comman.patch
Patch0106: 0106-spice-increase-the-verbosity-of-spice-section-in-qem.patch
Patch0107: 0107-qxl-update_area_io-guest_bug-on-invalid-parameters.patch
Patch0108: 0108-qxl-add-QXL_IO_MONITORS_CONFIG_ASYNC.patch
Patch0109: 0109-configure-print-spice-protocol-and-spice-server-vers.patch
Patch0110: 0110-fix-doc-of-using-raw-values-with-sendkey.patch
Patch0111: 0111-qapi-Fix-potential-NULL-pointer-segfault.patch
Patch0112: 0112-json-parser-Fix-potential-NULL-pointer-segfault.patch
Patch0113: 0113-pcie-drop-version_id-field-for-live-migration.patch
Patch0114: 0114-pcie_aer-clear-cmask-for-Advanced-Error-Interrupt-Me.patch
Patch0115: 0115-fix-entry-pointer-for-ELF-kernels-loaded-with-kernel.patch
Patch0116: 0116-lan9118-fix-multicast-filtering.patch
Patch0117: 0117-MIPS-user-Fix-reset-CPU-state-initialization.patch
Patch0118: 0118-Add-MAINTAINERS-entry-for-leon3.patch
Patch0119: 0119-musicpal-Fix-flash-mapping.patch
Patch0120: 0120-qemu-Use-valgrind-annotations-to-mark-kvm-guest-memo.patch
Patch0121: 0121-hw-wm8750-Fix-potential-buffer-overflow.patch
Patch0122: 0122-hw-mcf5206-Fix-buffer-overflow-for-MBAR-read-write.patch
Patch0123: 0123-use-libexecdir-instead-of-ignoring-it-first-and-rein.patch
Patch0124: 0124-socket-don-t-attempt-to-reconnect-a-TCP-socket-in-se.patch
Patch0125: 0125-Add-ability-to-force-enable-disable-of-tools-build.patch
Patch0126: 0126-usb-controllers-do-not-need-to-check-for-babble-them.patch
Patch0127: 0127-usb-core-Don-t-set-packet-state-to-complete-on-a-nak.patch
Patch0128: 0128-usb-core-Add-a-usb_ep_find_packet_by_id-helper-funct.patch
Patch0129: 0129-usb-core-Allow-the-first-packet-of-a-pipelined-ep-to.patch
Patch0130: 0130-Revert-ehci-don-t-flush-cache-on-doorbell-rings.patch
Patch0131: 0131-ehci-Validate-qh-is-not-changed-unexpectedly-by-the-.patch
Patch0132: 0132-ehci-Update-copyright-headers-to-reflect-recent-work.patch
Patch0133: 0133-ehci-Properly-cleanup-packets-on-cancel.patch
Patch0134: 0134-ehci-Properly-report-completed-but-not-yet-processed.patch
Patch0135: 0135-ehci-check-for-EHCI_ASYNC_FINISHED-first-in-ehci_fre.patch
Patch0136: 0136-ehci-trace-guest-bugs.patch
Patch0137: 0137-ehci-add-doorbell-trace-events.patch
Patch0138: 0138-ehci-Add-some-additional-ehci_trace_guest_bug-calls.patch
Patch0139: 0139-ehci-Fix-memory-leak-in-handling-of-NAK-ed-packets.patch
Patch0140: 0140-ehci-Handle-USB_RET_PROCERR-in-ehci_fill_queue.patch
Patch0141: 0141-ehci-Correct-a-comment-in-fetchqtd-packet-processing.patch
Patch0142: 0142-usb-redir-Never-return-USB_RET_NAK-for-async-handled.patch
Patch0143: 0143-usb-redir-Don-t-delay-handling-of-open-events-to-a-b.patch
Patch0144: 0144-usb-redir-Get-rid-of-async-struct-get-member.patch
Patch0145: 0145-usb-redir-Get-rid-of-local-shadow-copy-of-packet-hea.patch
Patch0146: 0146-usb-redir-Get-rid-of-unused-async-struct-dev-member.patch
Patch0147: 0147-usb-redir-Move-to-core-packet-id-and-queue-handling.patch
Patch0148: 0148-usb-redir-Return-babble-when-getting-more-bulk-data-.patch
Patch0149: 0149-Better-name-usb-braille-device.patch
Patch0150: 0150-usb-audio-fix-usb-version.patch
Patch0151: 0151-xhci-rip-out-background-transfer-code.patch
Patch0152: 0152-xhci-drop-buffering.patch
Patch0153: 0153-xhci-fix-runtime-write-tracepoint.patch
Patch0154: 0154-xhci-allow-bytewise-capability-register-reads.patch
Patch0155: 0155-qxl-dont-update-invalid-area.patch
Patch0156: 0156-usb-host-allow-emulated-non-async-control-requests-w.patch
Patch0157: 0157-qxl-better-cleanup-for-surface-destroy.patch
Patch0158: 0158-ehci-switch-to-new-style-memory-ops.patch
Patch0159: 0159-ehci-Fix-interrupts-stopping-when-Interrupt-Threshol.patch
Patch0160: 0160-ehci-Don-t-process-too-much-frames-in-1-timer-tick-v.patch
Patch0161: 0161-sheepdog-fix-savevm-and-loadvm.patch
Patch0162: 0162-ide-Fix-error-messages-from-static-code-analysis-no-.patch
Patch0163: 0163-block-curl-Fix-wrong-free-statement.patch
Patch0164: 0164-vdi-Fix-warning-from-clang.patch
Patch0165: 0165-block-fix-block-tray-status.patch
Patch0166: 0166-ahci-properly-reset-PxCMD-on-HBA-reset.patch
Patch0167: 0167-Don-t-require-encryption-password-for-qemu-img-info-.patch
Patch0168: 0168-block-Don-t-forget-to-delete-temporary-file.patch
Patch0169: 0169-hw-qxl-tracing-fixes.patch
Patch0170: 0170-configure-usbredir-fixes.patch
Patch0171: 0171-ehci-Don-t-set-seen-to-0-when-removing-unseen-queue-.patch
Patch0172: 0172-ehci-Walk-async-schedule-before-and-after-migration.patch
Patch0173: 0173-usb-redir-Revert-usb-redir-part-of-commit-93bfef4c.patch
Patch0174: 0174-uhci-Don-t-queue-up-packets-after-one-with-the-SPD-f.patch
Patch0175: 0175-slirp-Remove-wrong-type-casts-ins-debug-statements.patch
Patch0176: 0176-slirp-Fix-error-reported-by-static-code-analysis.patch
Patch0177: 0177-slirp-improve-TFTP-performance.patch
Patch0178: 0178-slirp-Handle-more-than-65535-blocks-in-TFTP-transfer.patch
Patch0179: 0179-slirp-Implement-TFTP-Blocksize-option.patch
Patch0180: 0180-srp-Don-t-use-QEMU_PACKED-for-single-elements-of-a-s.patch
Patch0181: 0181-Spelling-fixes-in-comments-and-documentation.patch
Patch0182: 0182-console-Clean-up-bytes-per-pixel-calculation.patch
Patch0183: 0183-qapi-Fix-enumeration-typo-error.patch
Patch0184: 0184-kvm-Fix-warning-from-static-code-analysis.patch
Patch0185: 0185-arch_init.c-add-missing-symbols-before-PRIu64-in-deb.patch
Patch0186: 0186-net-notify-iothread-after-flushing-queue.patch
Patch0187: 0187-e1000-flush-queue-whenever-can_receive-can-go-from-f.patch
Patch0188: 0188-xen-flush-queue-when-getting-an-event.patch
Patch0189: 0189-eepro100-Fix-network-hang-when-rx-buffers-run-out.patch
Patch0190: 0190-net-add-receive_disabled-logic-to-iov-delivery-path.patch
Patch0191: 0191-net-do-not-report-queued-packets-as-sent.patch
Patch0192: 0192-net-add-netdev-options-to-man-page.patch
Patch0193: 0193-net-clean-up-usbnet_receive.patch
Patch0194: 0194-net-fix-usbnet_receive-packet-drops.patch
Patch0195: 0195-net-broadcast-hub-packets-if-at-least-one-port-can-r.patch
Patch0196: 0196-net-asynchronous-send-receive-infrastructure-for-net.patch
Patch0197: 0197-net-EAGAIN-handling-for-net-socket.c-UDP.patch
Patch0198: 0198-net-EAGAIN-handling-for-net-socket.c-TCP.patch
Patch0199: 0199-configure-fix-seccomp-check.patch
Patch0200: 0200-configure-properly-check-if-lrt-and-lm-is-needed.patch
Patch0201: 0201-Revert-455aa1e08-and-c3767ed0eb.patch
Patch0202: 0202-qemu-char-BUGFIX-don-t-call-FD_ISSET-with-negative-f.patch
Patch0203: 0203-cpu_physical_memory_write_rom-needs-to-do-TB-invalid.patch
Patch0204: 0204-arch_init.c-Improve-soundhw-help-for-non-HAS_AUDIO_C.patch
Patch0205: 0205-xilinx_timer-Removed-comma-in-device-name.patch
Patch0206: 0206-xilinx_timer-Send-dbg-msgs-to-stderr-not-stdout.patch
Patch0207: 0207-xilinx.h-Error-check-when-setting-links.patch
Patch0208: 0208-xilinx_timer-Fix-a-compile-error-if-debug-enabled.patch
Patch0209: 0209-pflash_cfi01-fix-vendor-specific-extended-query.patch
Patch0210: 0210-MAINTAINERS-Add-entry-for-QOM-CPU.patch
Patch0211: 0211-iSCSI-We-need-to-support-SG_IO-also-from-iscsi_ioctl.patch
Patch0212: 0212-iSCSI-We-dont-need-to-explicitely-call-qemu_notify_e.patch
Patch0213: 0213-scsi-disk-introduce-check_lba_range.patch
Patch0214: 0214-scsi-disk-fix-check-for-out-of-range-LBA.patch
Patch0215: 0215-SCSI-Standard-INQUIRY-data-should-report-HiSup-flag-.patch
Patch0216: 0216-audio-Fix-warning-from-static-code-analysis.patch
Patch0217: 0217-qemu-ga-Remove-unreachable-code-after-g_error.patch
Patch0218: 0218-qemu-sockets-Fix-potential-memory-leak.patch
Patch0219: 0219-cadence_uart-Fix-buffer-overflow.patch
Patch0220: 0220-lm4549-Fix-buffer-overflow.patch
Patch0221: 0221-ioh3420-Remove-unreachable-code.patch
Patch0222: 0222-pflash_cfi01-Fix-warning-caused-by-unreachable-code.patch
Patch0223: 0223-curses-don-t-initialize-curses-when-qemu-is-daemoniz.patch
Patch0224: 0224-TextConsole-saturate-escape-parameter-in-TTY_STATE_C.patch
Patch0225: 0225-linux-user-Remove-redundant-null-check-and-replace-f.patch
Patch0226: 0226-net-socket-Fix-compiler-warning-regression-for-MinGW.patch
Patch0227: 0227-w32-Always-use-standard-instead-of-native-format-str.patch
Patch0228: 0228-w32-Add-implementation-of-gmtime_r-localtime_r.patch
Patch0229: 0229-blockdev-preserve-readonly-and-snapshot-states-acros.patch
Patch0230: 0230-block-correctly-set-the-keep_read_only-flag.patch
Patch0231: 0231-configure-Allow-builds-without-any-system-or-user-em.patch
Patch0232: 0232-Refactor-inet_connect_opts-function.patch
Patch0233: 0233-Separate-inet_connect-into-inet_connect-blocking-and.patch
Patch0234: 0234-Fix-address-handling-in-inet_nonblocking_connect.patch
Patch0235: 0235-Clear-handler-only-for-valid-fd.patch
Patch0236: 0236-pl190-fix-read-of-VECTADDR.patch
Patch0237: 0237-hw-armv7m_nvic-Correctly-register-GIC-region-when-se.patch
Patch0238: 0238-Versatile-Express-Fix-NOR-flash-0-address-and-remove.patch
Patch0239: 0239-i386-kvm-bit-10-of-CPUID-8000_0001-.EDX-is-reserved.patch
Patch0240: 0240-fpu-softfloat.c-Return-correctly-signed-values-from-.patch
Patch0241: 0241-pseries-Don-t-test-for-MSR_PR-for-hypercalls-under-K.patch
Patch0242: 0242-update-VERSION-for-v1.2.1.patch

# Stable 1.2.2 patches
Patch0301: 0301-configure-Fix-CONFIG_QEMU_HELPERDIR-generation.patch
Patch0302: 0302-fix-CONFIG_QEMU_HELPERDIR-generation-again.patch
Patch0303: 0303-ui-vnc-Only-report-use-TIGHT_PNG-encoding-if-enabled.patch
Patch0304: 0304-vnc-fix-info-vnc-with-vnc-.-reverse-on.patch
Patch0305: 0305-uhci-Raise-interrupt-when-requested-even-for-non-act.patch
Patch0306: 0306-hw-qxl-qxl_dirty_surfaces-use-uintptr_t.patch
Patch0307: 0307-qxl-always-update-displaysurface-on-resize.patch
Patch0308: 0308-rtc-fix-overflow-in-mktimegm.patch
Patch0309: 0309-hw-Fix-return-value-check-for-bdrv_read-bdrv_write.patch
Patch0310: 0310-target-i386-Allow-tsc-frequency-to-be-larger-then-2..patch
Patch0311: 0311-e1000-drop-check_rxov-always-treat-RX-ring-with-RDH-.patch
Patch0312: 0312-memory-fix-rendering-of-a-region-obscured-by-another.patch
Patch0313: 0313-s390x-fix-initrd-in-virtio-machine.patch
Patch0314: 0314-PPC-Bamboo-Fix-memory-size-DT-property.patch
Patch0315: 0315-target-sparc64-disable-VGA-cirrus.patch
Patch0316: 0316-xhci-fix-usb-name-in-caps.patch
Patch0317: 0317-tools-initialize-main-loop-before-block-layer.patch
Patch0318: 0318-m68k-Return-semihosting-errno-values-correctly.patch
Patch0319: 0319-nbd-fixes-to-read-only-handling.patch
Patch0320: 0320-mips-malta-fix-CBUS-UART-interrupt-pin.patch
Patch0321: 0321-target-mips-fix-wrong-microMIPS-opcode-encoding.patch
Patch0322: 0322-tcg-arm-fix-TLB-access-in-qemu-ld-st-ops.patch
Patch0323: 0323-tcg-arm-fix-cross-endian-qemu_st16.patch
Patch0324: 0324-target-openrisc-remove-conflicting-definitions-from-.patch
Patch0325: 0325-configure-avoid-compiler-warning-in-pipe2-detection.patch
Patch0326: 0326-qcow2-Fix-refcount-table-size-calculation.patch
Patch0327: 0327-tci-Fix-type-of-tci_read_label.patch
Patch0328: 0328-block-Fix-regression-for-MinGW-assertion-caused-by-s.patch
Patch0329: 0329-qom-dynamic_cast-of-NULL-is-always-NULL.patch
Patch0330: 0330-hmp-do-not-crash-on-invalid-SCSI-hotplug.patch
Patch0331: 0331-PPC-Fix-missing-TRACE-exception.patch
Patch0332: 0332-qom-fix-refcount-of-non-heap-allocated-objects.patch
Patch0333: 0333-qapi-handle-visitor-type_size-in-QapiDeallocVisitor.patch
Patch0334: 0334-qapi-fix-qapi_dealloc_type_size-parameter-type.patch
Patch0335: 0335-iscsi-fix-segfault-in-url-parsing.patch
Patch0336: 0336-iscsi-fix-deadlock-during-login.patch
Patch0337: 0337-iscsi-do-not-assume-device-is-zero-initialized.patch
Patch0338: 0338-virtio-scsi-Fix-some-endian-bugs-with-virtio-scsi.patch
Patch0339: 0339-virtio-scsi-Fix-subtle-guest-endian-bug.patch
Patch0340: 0340-qxl-reload-memslots-after-migration-when-qxl-is-in-U.patch
Patch0341: 0341-usb-fail-usbdevice_create-when-there-is-no-USB-bus.patch
Patch0342: 0342-stream-fix-ratelimit_set_speed.patch
Patch0343: 0343-e1000-Discard-packets-that-are-too-long-if-SBP-and-L.patch

# chardev flow control series
Patch0401: 0401-update-VERSION-for-v1.2.2.patch
Patch0402: 0402-char-Split-out-tcp-socket-close-code-in-a-separate-f.patch
Patch0403: 0403-char-Add-a-QemuChrHandlers-struct-to-initialise-char.patch
Patch0404: 0404-iohandlers-Add-enable-disable_write_fd_handler-funct.patch
Patch0405: 0405-char-Add-framework-for-a-write-unblocked-callback.patch
Patch0406: 0406-char-Update-send_all-to-handle-nonblocking-chardev-w.patch
Patch0407: 0407-char-Equip-the-unix-tcp-backend-to-handle-nonblockin.patch
Patch0408: 0408-char-Throttle-when-host-connection-is-down.patch
Patch0409: 0409-virtio-console-Enable-port-throttling-when-chardev-i.patch
Patch0410: 0410-spice-qemu-char.c-add-throttling.patch
Patch0411: 0411-spice-qemu-char.c-remove-intermediate-buffer.patch
Patch0412: 0412-usb-redir-Add-flow-control-support.patch
Patch0413: 0413-virtio-serial-bus-replay-guest_open-on-migration.patch
Patch0414: 0414-char-Disable-write-callback-if-throttled-chardev-is-.patch

# spice seamless migration, dynamic monitors, spice/qxl bug fixes
Patch0501: 0501-qxl-disallow-unknown-revisions.patch
Patch0502: 0502-spice-make-number-of-surfaces-runtime-configurable.patch
Patch0503: 0503-qxl-Add-set_client_capabilities-interface-to-QXLInte.patch
Patch0504: 0504-Remove-ifdef-QXL_COMMAND_FLAG_COMPAT_16BPP.patch
Patch0505: 0505-spice-switch-to-queue-for-vga-mode-updates.patch
Patch0506: 0506-spice-split-qemu_spice_create_update.patch
Patch0507: 0507-spice-add-screen-mirror.patch
Patch0508: 0508-spice-send-updates-only-for-changed-screen-content.patch
Patch0509: 0509-qxl-Ignore-set_client_capabilities-pre-post-migrate.patch
Patch0510: 0510-qxl-add-trace-event-for-QXL_IO_LOG.patch
Patch0511: 0511-hw-qxl-support-client-monitor-configuration-via-devi.patch
Patch0512: 0512-qxl-update_area_io-cleanup-invalid-parameters-handli.patch
Patch0513: 0513-qxl-fix-range-check-for-rev3-io-commands.patch
Patch0514: 0514-qxl-vnc-register-a-vm-state-change-handler-for-dummy.patch
Patch0515: 0515-hw-qxl-exit-on-failure-to-register-qxl-interface.patch
Patch0516: 0516-hw-qxl-fix-condition-for-exiting-guest_bug.patch
Patch0517: 0517-hw-qxl-qxl_send_events-nop-if-stopped.patch
Patch0518: 0518-qxl-call-dpy_gfx_resize-when-entering-vga-mode.patch
Patch0519: 0519-spice-fix-initialization-order.patch
Patch0520: 0520-spice-add-new-spice-server-callbacks-to-ui-spice-dis.patch
Patch0521: 0521-qxl-save-qemu_create_displaysurface_from-result.patch

# usb-redir live-migration and misc bits from upstream master
Patch0601: 0601-usb-redir-Convert-to-new-libusbredirparser-0.5-API.patch
Patch0602: 0602-usb-redir-Set-ep-max_packet_size-if-available.patch
Patch0603: 0603-usb-redir-Add-a-usbredir_reject_device-helper-functi.patch
Patch0604: 0604-usb-redir-Ensure-our-peer-has-the-necessary-caps-whe.patch
Patch0605: 0605-usb-redir-Enable-pipelining-for-bulk-endpoints.patch
Patch0606: 0606-xhci-move-device-lookup-into-xhci_setup_packet.patch
Patch0607: 0607-xhci-implement-mfindex.patch
Patch0608: 0608-xhci-iso-xfer-support.patch
Patch0609: 0609-xhci-trace-cc-codes-in-cleartext.patch
Patch0610: 0610-xhci-add-trace_usb_xhci_ep_set_dequeue.patch
Patch0611: 0611-xhci-update-register-layout.patch
Patch0612: 0612-xhci-update-port-handling.patch
Patch0613: 0613-usb3-superspeed-descriptors.patch
Patch0614: 0614-usb3-superspeed-endpoint-companion.patch
Patch0615: 0615-usb3-bos-decriptor.patch
Patch0616: 0616-usb-storage-usb3-support.patch
Patch0617: 0617-xhci-fix-cleanup-msi.patch
Patch0618: 0618-xhci-rework-interrupt-handling.patch
Patch0619: 0619-xhci-add-msix-support.patch
Patch0620: 0620-xhci-move-register-update-into-xhci_intr_raise.patch
Patch0621: 0621-xhci-add-XHCIInterrupter.patch
Patch0622: 0622-xhci-prepare-xhci_runtime_-read-write-for-multiple-i.patch
Patch0623: 0623-xhci-pick-target-interrupter.patch
Patch0624: 0624-xhci-support-multiple-interrupters.patch
Patch0625: 0625-xhci-kill-xhci_mem_-read-write-dispatcher-functions.patch
Patch0626: 0626-usb-redir-Change-cancelled-packet-code-into-a-generi.patch
Patch0627: 0627-usb-redir-Add-an-already_in_flight-packet-id-queue.patch
Patch0628: 0628-usb-redir-Store-max_packet_size-in-endp_data.patch
Patch0629: 0629-usb-redir-Add-support-for-migration.patch
Patch0630: 0630-usb-redir-Add-chardev-open-close-debug-logging.patch
Patch0631: 0631-usb-redir-Revert-usb-redir-part-of-commit-93bfef4c.patch
Patch0632: 0632-ehci-Fix-interrupt-packet-MULT-handling.patch
Patch0633: 0633-usb-redir-Adjust-pkg-config-check-for-usbredirparser.patch
Patch0634: 0634-usb-redir-Change-usbredir_open_chardev-into-usbredir.patch
Patch0635: 0635-usb-redir-Don-t-make-migration-fail-in-none-seamless.patch

# Non upstream build fix, http://www.spinics.net/lists/kvm/msg80589.html
Patch0701: 0701-mips-Fix-link-error-with-piix4_pm_init.patch
# Add ./configure --disable-kvm-options
Patch0702: 0702-configure-Add-disable-kvm-options.patch
# Fix loading arm initrd if kernel is very large (bz #862766)
Patch0703: 0703-arm_boot-Change-initrd-load-address-to-halfway-throu.patch
# Don't use reserved word 'function' in systemtap files (bz #871286)
Patch0704: 0704-dtrace-backend-add-function-to-reserved-words.patch
# libcacard build fixes
Patch0705: 0705-libcacard-fix-missing-symbols-in-libcacard.so.patch
Patch0706: 0706-configure-move-vscclient-binary-under-libcacard.patch
Patch0707: 0707-libcacard-fix-missing-symbol-in-libcacard.so.patch
# Fix libvirt + seccomp combo (bz #855162)
Patch0708: 0708-seccomp-adding-new-syscalls-bugzilla-855162.patch
# CVE-2012-6075: Buffer overflow in e1000 nic (bz #889301, bz #889304)
Patch0709: 0709-e1000-Discard-oversized-packets-based-on-SBP-LPE.patch
# Fix boot hang if console is not connected (bz #894451)
Patch0710: 0710-Revert-serial-fix-retry-logic.patch
# Fix segfault with zero length virtio-scsi disk (bz #847549)
Patch0711: 0711-scsi-fix-segfault-with-0-byte-disk.patch
# Fixes for iscsi dep
Patch0712: 0712-iscsi-look-for-pkg-config-file-too.patch
# Fix -vga vmware crashes (bz #836260)
Patch0713: 0713-vmware_vga-fix-out-of-bounds-and-invalid-rects-updat.patch
# Fix possible crash with VNC and qxl (bz #919777)
Patch0714: 0714-qxl-better-vga-init-in-enter_vga_mode.patch
# Fix mellanox card passthrough (bz #907996)
Patch0715: 0715-pci-assign-Enable-MSIX-on-device-to-match-guest.patch
# Fix QXL migration from F17 to F18 (bz #907916)
Patch0716: 0716-qxl-change-rom-size-to-8192.patch
Patch0717: 0717-qxl-Add-rom_size-compat-property-fix-migration-from-.patch
# Fix use after free + assert in ehci (bz #890320)
Patch0718: 0718-ehci-Don-t-access-packet-after-freeing-it.patch
Patch0719: 0719-ehci-Fixup-q-qtdaddr-after-cancelling-an-already-com.patch
Patch0720: 0720-ehci_free_packet-Discard-finished-packets-when-the-q.patch
# Fix booting 3.8 kernels with qemu-system-arm (bz #922796)
Patch0721: 0721-hw-arm_sysctl-Clear-sysctl-cfgctrl-start-bit.patch
# Fix crash with -vga qxl, sdl, and F19 guest (bz #949126)
Patch0722: 0722-console-remove-DisplayAllocator.patch
# CVE-2013-1922: qemu-nbd block format auto-detection vulnerability (bz
# #952574, bz #923219)
Patch0723: 0723-Add-f-FMT-format-FMT-arg-to-qemu-nbd.patch
# Fix building docs with f19 texinfo
Patch0724: 0724-docs-Fix-generating-qemu-doc.html-with-texinfo-5.patch
# CVE-2013-2007: Fix qemu-ga file creation permissions (bz #956082, bz
# #969455)
Patch0725: 0725-qga-set-umask-0077-when-daemonizing-CVE-2013-2007.patch
# Fix rtl8139 + windows 7 + large transfers (bz #970240)
Patch0726: 0726-rtl8139-flush-queued-packets-when-RxBufPtr-is-writte.patch
# Fix build with latest dtc (bz #1003187)
Patch0727: 0727-configure-dtc-Probe-for-libfdt_env.h.patch

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
BuildRequires: SDL-devel 
# qemu doesn't build with gcc 4.x
BuildRequires: compat-gcc-34 
BuildRequires: zlib-devel 
BuildRequires: e2fsprogs-devel
BuildRequires: kernel-devel
BuildRequires: glib2-devel
BuildRequires: pciutils-devel
# for the docs
BuildRequires: texi2html
# kvm kernel side is only x86/x86_64 as that's where the hardware is
ExclusiveArch: %{ix86} x86_64
Requires: initscripts >= 8.08-1
%if "%dist" == ".el5"
Requires: kmod-kvm
%endif
%if 0%{?remove_header_extension_to_large} == 1
Epoch: 3
%else
Epoch: 2
%endif

%description
KVM (for Kernel-based Virtual Machine) is a full virtualization solution 
for Linux on x86 hardware. 

Using KVM, one can run multiple virtual machines running unmodified Linux 
or Windows images. Each virtual machine has private virtualized hardware: 
a network card, disk, graphics adapter, etc.

%prep
%setup -q -n qemu-kvm-1.2.0
%if "%dist" == ".el5"
%patch0 -p1
%endif
%patch0001 -p1
%patch0002 -p1
%patch0003 -p1
%patch0004 -p1
%patch0005 -p1
%patch0006 -p1
%patch0007 -p1
%patch0008 -p1
%patch0009 -p1
%patch0010 -p1
%patch0011 -p1
%patch0012 -p1
%patch0013 -p1
%patch0014 -p1
%patch0015 -p1
%patch0016 -p1
%patch0017 -p1
%patch0018 -p1
%patch0019 -p1
%patch0020 -p1
%patch0021 -p1
%patch0022 -p1
%patch0023 -p1
%patch0024 -p1
%patch0025 -p1
%patch0026 -p1
%patch0027 -p1
%patch0028 -p1
%patch0029 -p1
%patch0030 -p1
%patch0031 -p1
%patch0032 -p1
%patch0033 -p1
%patch0034 -p1
%patch0035 -p1
%patch0036 -p1
%patch0037 -p1
%patch0038 -p1
%patch0039 -p1
%patch0040 -p1
%patch0041 -p1
%patch0042 -p1
%patch0043 -p1
%patch0044 -p1
%patch0045 -p1
%patch0046 -p1
%patch0047 -p1
%patch0048 -p1
%patch0049 -p1
%patch0050 -p1
%patch0051 -p1
%patch0052 -p1
%patch0053 -p1
%patch0054 -p1
%patch0055 -p1
%patch0056 -p1
%patch0057 -p1
%patch0058 -p1
%patch0059 -p1
%patch0060 -p1
%patch0061 -p1
%patch0062 -p1
%patch0063 -p1
%patch0064 -p1
%patch0065 -p1
%patch0066 -p1
%patch0067 -p1
%patch0068 -p1
%patch0069 -p1
%patch0070 -p1
%patch0071 -p1
%patch0072 -p1
%patch0073 -p1
%patch0074 -p1
%patch0075 -p1
%patch0076 -p1
%patch0077 -p1
%patch0078 -p1
%patch0079 -p1
%patch0080 -p1
%patch0081 -p1
%patch0082 -p1
%patch0083 -p1
%patch0084 -p1
%patch0085 -p1
%patch0086 -p1
%patch0087 -p1
%patch0088 -p1
%patch0089 -p1
%patch0090 -p1
%patch0091 -p1
%patch0092 -p1
%patch0093 -p1
%patch0094 -p1
%patch0095 -p1
%patch0096 -p1
%patch0097 -p1
%patch0098 -p1
%patch0099 -p1
%patch0100 -p1
%patch0101 -p1
%patch0102 -p1
%patch0103 -p1
%patch0104 -p1
%patch0105 -p1
%patch0106 -p1
%patch0107 -p1
%patch0108 -p1
%patch0109 -p1
%patch0110 -p1
%patch0111 -p1
%patch0112 -p1
%patch0113 -p1
%patch0114 -p1
%patch0115 -p1
%patch0116 -p1
%patch0117 -p1
%patch0118 -p1
%patch0119 -p1
%patch0120 -p1
%patch0121 -p1
%patch0122 -p1
%patch0123 -p1
%patch0124 -p1
%patch0125 -p1
%patch0126 -p1
%patch0127 -p1
%patch0128 -p1
%patch0129 -p1
%patch0130 -p1
%patch0131 -p1
%patch0132 -p1
%patch0133 -p1
%patch0134 -p1
%patch0135 -p1
%patch0136 -p1
%patch0137 -p1
%patch0138 -p1
%patch0139 -p1
%patch0140 -p1
%patch0141 -p1
%patch0142 -p1
%patch0143 -p1
%patch0144 -p1
%patch0145 -p1
%patch0146 -p1
%patch0147 -p1
%patch0148 -p1
%patch0149 -p1
%patch0150 -p1
%patch0151 -p1
%patch0152 -p1
%patch0153 -p1
%patch0154 -p1
%patch0155 -p1
%patch0156 -p1
%patch0157 -p1
%patch0158 -p1
%patch0159 -p1
%patch0160 -p1
%patch0161 -p1
%patch0162 -p1
%patch0163 -p1
%patch0164 -p1
%patch0165 -p1
%patch0166 -p1
%patch0167 -p1
%patch0168 -p1
%patch0169 -p1
%patch0170 -p1
%patch0171 -p1
%patch0172 -p1
%patch0173 -p1
%patch0174 -p1
%patch0175 -p1
%patch0176 -p1
%patch0177 -p1
%patch0178 -p1
%patch0179 -p1
%patch0180 -p1
%patch0181 -p1
%patch0182 -p1
%patch0183 -p1
%patch0184 -p1
%patch0185 -p1
%patch0186 -p1
%patch0187 -p1
%patch0188 -p1
%patch0189 -p1
%patch0190 -p1
%patch0191 -p1
%patch0192 -p1
%patch0193 -p1
%patch0194 -p1
%patch0195 -p1
%patch0196 -p1
%patch0197 -p1
%patch0198 -p1
%patch0199 -p1
%patch0200 -p1
%patch0201 -p1
%patch0202 -p1
%patch0203 -p1
%patch0204 -p1
%patch0205 -p1
%patch0206 -p1
%patch0207 -p1
%patch0208 -p1
%patch0209 -p1
%patch0210 -p1
%patch0211 -p1
%patch0212 -p1
%patch0213 -p1
%patch0214 -p1
%patch0215 -p1
%patch0216 -p1
%patch0217 -p1
%patch0218 -p1
%patch0219 -p1
%patch0220 -p1
%patch0221 -p1
%patch0222 -p1
%patch0223 -p1
%patch0224 -p1
%patch0225 -p1
%patch0226 -p1
%patch0227 -p1
%patch0228 -p1
%patch0229 -p1
%patch0230 -p1
%patch0231 -p1
%patch0232 -p1
%patch0233 -p1
%patch0234 -p1
%patch0235 -p1
%patch0236 -p1
%patch0237 -p1
%patch0238 -p1
%patch0239 -p1
%patch0240 -p1
%patch0241 -p1
%patch0242 -p1

# Stable 1.2.2 patches
%patch0301 -p1
%patch0302 -p1
%patch0303 -p1
%patch0304 -p1
%patch0305 -p1
%patch0306 -p1
%patch0307 -p1
%patch0308 -p1
%patch0309 -p1
%patch0310 -p1
%patch0311 -p1
%patch0312 -p1
%patch0313 -p1
%patch0314 -p1
%patch0315 -p1
%patch0316 -p1
%patch0317 -p1
%patch0318 -p1
%patch0319 -p1
%patch0320 -p1
%patch0321 -p1
%patch0322 -p1
%patch0323 -p1
%patch0324 -p1
%patch0325 -p1
%patch0326 -p1
%patch0327 -p1
%patch0328 -p1
%patch0329 -p1
%patch0330 -p1
%patch0331 -p1
%patch0332 -p1
%patch0333 -p1
%patch0334 -p1
%patch0335 -p1
%patch0336 -p1
%patch0337 -p1
%patch0338 -p1
%patch0339 -p1
%patch0340 -p1
%patch0341 -p1
%patch0342 -p1
%patch0343 -p1

# chardev flow control series
%patch0401 -p1
%patch0402 -p1
%patch0403 -p1
%patch0404 -p1
%patch0405 -p1
%patch0406 -p1
%patch0407 -p1
%patch0408 -p1
%patch0409 -p1
%patch0410 -p1
%patch0411 -p1
%patch0412 -p1
%patch0413 -p1
%patch0414 -p1

# spice seamless migration, dynamic monitors, spice/qxl bug fixes
%patch0501 -p1
%patch0502 -p1
%patch0503 -p1
%patch0504 -p1
%patch0505 -p1
%patch0506 -p1
%patch0507 -p1
%patch0508 -p1
%patch0509 -p1
%patch0510 -p1
%patch0511 -p1
%patch0512 -p1
%patch0513 -p1
%patch0514 -p1
%patch0515 -p1
%patch0516 -p1
%patch0517 -p1
%patch0518 -p1
%patch0519 -p1
%patch0520 -p1
%patch0521 -p1

# usb-redir live-migration and misc bits from upstream master
%patch0601 -p1
%patch0602 -p1
%patch0603 -p1
%patch0604 -p1
%patch0605 -p1
%patch0606 -p1
%patch0607 -p1
%patch0608 -p1
%patch0609 -p1
%patch0610 -p1
%patch0611 -p1
%patch0612 -p1
%patch0613 -p1
%patch0614 -p1
%patch0615 -p1
%patch0616 -p1
%patch0617 -p1
%patch0618 -p1
%patch0619 -p1
%patch0620 -p1
%patch0621 -p1
%patch0622 -p1
%patch0623 -p1
%patch0624 -p1
%patch0625 -p1
%patch0626 -p1
%patch0627 -p1
%patch0628 -p1
%patch0629 -p1
%patch0630 -p1
%patch0631 -p1
%patch0632 -p1
%patch0633 -p1
%patch0634 -p1
%patch0635 -p1

# Non upstream build fix, http://www.spinics.net/lists/kvm/msg80589.html
%patch0701 -p1
# Add ./configure --disable-kvm-options
%patch0702 -p1
# Fix loading arm initrd if kernel is very large (bz #862766)
%patch0703 -p1
# Don't use reserved word 'function' in systemtap files (bz #871286)
%patch0704 -p1
# libcacard build fixes
%patch0705 -p1
%patch0706 -p1
%patch0707 -p1
# Fix libvirt + seccomp combo (bz #855162)
%patch0708 -p1
# CVE-2012-6075: Buffer overflow in e1000 nic (bz #889301, bz #889304)
%patch0709 -p1
# Fix boot hang if console is not connected (bz #894451)
%patch0710 -p1
# Fix segfault with zero length virtio-scsi disk (bz #847549)
%patch0711 -p1
# Fixes for iscsi dep
%patch0712 -p1
# Fix -vga vmware crashes (bz #836260)
%patch0713 -p1
# Fix possible crash with VNC and qxl (bz #919777)
%patch0714 -p1
# Fix mellanox card passthrough (bz #907996)
%patch0715 -p1
# Fix QXL migration from F17 to F18 (bz #907916)
%patch0716 -p1
%patch0717 -p1
# Fix use after free + assert in ehci (bz #890320)
%patch0718 -p1
%patch0719 -p1
%patch0720 -p1
# Fix booting 3.8 kernels with qemu-system-arm (bz #922796)
%patch0721 -p1
# Fix crash with -vga qxl, sdl, and F19 guest (bz #949126)
%patch0722 -p1
# CVE-2013-1922: qemu-nbd block format auto-detection vulnerability (bz
# #952574, bz #923219)
%patch0723 -p1
# Fix building docs with f19 texinfo
%patch0724 -p1
# CVE-2013-2007: Fix qemu-ga file creation permissions (bz #956082, bz
# #969455)
%patch0725 -p1
# Fix rtl8139 + windows 7 + large transfers (bz #970240)
%patch0726 -p1
# Fix build with latest dtc (bz #1003187)
%patch0727 -p1


%build
export CPATH=/lib/modules/%{kversion}/build/include
./configure --prefix=%{_prefix} \
            --sysconfdir=%{_sysconfdir} \
            --target-list="x86_64-softmmu"
# we need to install the data bits in a different path
sed -i 's/CONFIG_QEMU_SHAREDIR \"\/usr\/share\/qemu\"/CONFIG_QEMU_SHAREDIR \"\/usr\/share\/kvm\"/' config-host.mak
sed -i 's/\/share\/qemu$/\/share\/kvm/' config-host.mak
sed -i 's/\/share\/doc\/qemu$/\/share\/doc\/kvm/' config-host.mak

# we can't use RPM_OPT_FLAGS for the same reasons as qemu (#208026) for the
# qemu bits.  so let's set it for the other pieces.  this requires some
# manual keeping up of what is in the kvm tree.
sed -i 's/CFLAGS =/CFLAGS +=/' Makefile
make %{?_smp_mflags} $buildldflags

%install
export CPATH=/lib/modules/%{kversion}/build/include
rm -rf $RPM_BUILD_ROOT
make install DESTDIR=${RPM_BUILD_ROOT}

rm -f $RPM_BUILD_ROOT/%{_bindir}/qemu-img
# no shared lib, static libs
rm -fr $RPM_BUILD_ROOT/%{_includedir} $RPM_BUILD_ROOT/%{_libdir}

mkdir -p $RPM_BUILD_ROOT/%{_bindir}
mv $RPM_BUILD_ROOT/%{_bindir}/qemu-system-x86_64 $RPM_BUILD_ROOT/%{_bindir}/qemu-kvm

mkdir -p $RPM_BUILD_ROOT/%{_sysconfdir}/sysconfig/modules
install -m 0755 %{SOURCE1} $RPM_BUILD_ROOT/%{_sysconfdir}/sysconfig/modules/kvm.modules

rm -rf $RPM_BUILD_ROOT/%{_bindir}/qemu-nbd $RPM_BUILD_ROOT/%{_bindir}/qemu-io
rm -f $RPM_BUILD_ROOT/%{_sysconfdir}/qemu/target-x86_64.conf

%clean
rm -rf $RPM_BUILD_ROOT

%post
touch %{_sysconfdir}/qemu/target-x86_64.conf

%files
%defattr(-,root,root,-)
%{_bindir}/qemu-kvm
#%{_bindir}/qemu-nbd
%{_bindir}/qemu-ga
#%{_bindir}/qemu-io
%{_datadir}/kvm/*
%{_sysconfdir}/sysconfig/modules/kvm.modules
/usr/libexec/qemu-bridge-helper

%changelog
* Thu Jun 26 2014 Carlos Rodrigues <cmar@eurotux.com> - 1.2.2-1
- fix /etc/qemu/target-x86_64.conf conflict with xen-runtime

* Fri Jun 13 2014 Carlos Rodrigues <cmar@eurotux.com> - 1.2.2-1
- apply bundle of patchs from qemu-1.2.2-14.fc18.src.rpm 

* Fri Nov 29 2013 Carlos Rodrigues <cmar@eurotux.com> - 1.2.0-6
- Epoch 2

* Fri Sep 13 2013 Nuno Fernandes <npf@eurotux.com> - 1.2.0-4
- Don't provide target-x86_64.conf

* Fri Mar 15 2013 Nuno Fernandes <npf@eurotux.com> - 1.2.0-3
- Apply all patches from qemu-1.2.0-25.fc19.src.rpm

* Wed Nov 28 2012 Nuno Fernandes <npf@eurotux.com> - 1.2.0-2
- patch qemu-kvm.remove-header-extension-to-large.patch

* Wed Nov 28 2012 Nuno Fernandes <npf@eurotux.com> - 1.2.0-1
- update to kvm-1.2.0

* Wed Nov 28 2012 Nuno Fernandes <npf@eurotux.com> - 0.15.1-1
- update to kvm-0.15.1

* Wed Apr 25 2012 Nuno Fernandes <npf@eurotux.com> - 0.15.0
- update to kvm-0.15.0

* Wed Jul 1 2009 Thomas Uphill <uphill@ias.edu> - 88-1
- update to kvm-88

* Wed Jul 1 2009 Thomas Uphill <uphill@ias.edu> - 87-1
- update to kvm-87

* Tue May 19 2009 Thomas Uphill <uphill@ias.edu> - 86-1
- update to kvm-86

* Tue May 19 2009 Thomas Uphill <uphill@ias.edu> - 85-1
- update to kvm-85

* Thu Apr 16 2009 Thomas Uphill <uphill@ias.edu> - 84-1
- update to kvm-84

* Sat Sep 08 2007 Daniel de Kok <daniel@centos.org> - 36-1
- update to kvm-36

* Mon Aug 20 2007 Daniel de Kok <daniel@centos.org> - 35-1
- update to kvm-35

* Sat Jul 28 2007 Daniel de Kok <daniel@centos.org> - 33-1
- update to kvm-33

* Wed Jul 18 2007 Daniel de Kok <daniel@centos.org> - 29-1
- update to kvm-29

* Mon Jun 18 2007 Daniel de Kok <daniel@centos.org> - 28-1
- update to kvm-28

* Wed May 16 2007 Jeremy Katz <katzj@redhat.com> - 24-1
- update to kvm-24

* Wed Apr 25 2007 Jeremy Katz <katzj@redhat.com> - 19-2
- fix kernel and ramdisk being specified on the command line (#237879)

* Tue Apr 17 2007 Jeremy Katz <katzj@redhat.com> - 19-1
- update to kvm-19
- use rtl8139 as the default nic emulation instead of ne2k_pci (#236790)

* Mon Mar 26 2007 Jeremy Katz <katzj@redhat.com> - 15-2
- add file so that kvm modules get loaded on boot

* Wed Mar  7 2007 Jeremy Katz <katzj@redhat.com> - 15-1
- update to kvm-15

* Mon Feb 26 2007 Jeremy Katz <katzj@redhat.com> - 14-2
- use default optflags for non-qemu pieces (#230012)

* Fri Feb 23 2007 Jeremy Katz <katzj@redhat.com> - 14-1
- update to kvm-14
- note: this requires a kernel that's 2.6.21-rc1 or newer

* Mon Feb 19 2007 Jeremy Katz <katzj@redhat.com> - 12-3
- add the buildrequires for the docs to build

* Mon Feb 19 2007 Jeremy Katz <katzj@redhat.com> - 12-2
- include bios and keymaps in the kvm package since we need a slightly 
  different version for kvm now
- include man page

* Fri Jan 26 2007 Jeremy Katz <katzj@redhat.com> - 12-1
- update to kvm-12
- add qemu patch for better ATAPI DMA support (which works with ata_piix)

* Fri Jan 19 2007 Jeremy Katz <katzj@redhat.com> - 11-1
- update to kvm-11

* Fri Jan  5 2007 Jeremy Katz <katzj@redhat.com> - 9-1
- update to kvm-9

* Wed Jan  3 2007 Jeremy Katz <katzj@redhat.com> - 7-4
- actually build without -devel...

* Wed Dec 20 2006 Jeremy Katz <katzj@redhat.com> - 7-3
- remove the -devel subpackage since there's no shared lib upstream yet
- direct download link for source 

* Tue Dec 19 2006 Jeremy Katz <katzj@redhat.com> - 7-2
- BR e2fsprogs-devel

* Tue Dec 19 2006 Jeremy Katz <katzj@redhat.com> - 7-1
- Initial build 

