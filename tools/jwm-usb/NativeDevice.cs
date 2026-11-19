using System;
using System.ComponentModel;
using System.IO;
using System.Runtime.InteropServices;
using Microsoft.Win32.SafeHandles;

namespace GuardOps.JwmUsb
{
    public sealed class NativeDevice : IDisposable
    {
        [UnmanagedFunctionPointer(CallingConvention.Winapi)]
        private delegate int NoArguments();

        [UnmanagedFunctionPointer(CallingConvention.Winapi)]
        private delegate int VerifyFunction(int value);

        [UnmanagedFunctionPointer(CallingConvention.Winapi, CharSet = CharSet.Ansi)]
        private delegate int ReadFunction([MarshalAs(UnmanagedType.LPStr)] string filename, int encryption);

        private readonly LibraryHandle library;
        private readonly NoArguments open;
        private readonly NoArguments identity;
        private readonly VerifyFunction verify;
        private readonly ReadFunction read;
        private readonly NoArguments close;

        public NativeDevice(string dllPath)
        {
            if (Environment.OSVersion.Platform != PlatformID.Win32NT || IntPtr.Size != 8)
                throw new PlatformNotSupportedException("The USB SDK requires a 64-bit Windows process.");
            if (!Path.IsPathRooted(dllPath))
                throw new ArgumentException("Supply an absolute path to the trusted x64 libJComm.dll.", "dllPath");

            // Search dependencies only beside this DLL and in the Windows system directory.
            library = LoadLibraryExW(Path.GetFullPath(dllPath), IntPtr.Zero, 0x00000100 | 0x00000800);
            if (library.IsInvalid)
            {
                int error = Marshal.GetLastWin32Error();
                library.Dispose();
                throw new Win32Exception(error, "Cannot load the x64 SDK (Win32 " + error + "). Check its architecture and native dependencies.");
            }

            bool ready = false;
            try
            {
                open = (NoArguments)Marshal.GetDelegateForFunctionPointer(Find("OpenDevice"), typeof(NoArguments));
                identity = (NoArguments)Marshal.GetDelegateForFunctionPointer(Find("GetDeviceId"), typeof(NoArguments));
                verify = (VerifyFunction)Marshal.GetDelegateForFunctionPointer(Find("Verify"), typeof(VerifyFunction));
                read = (ReadFunction)Marshal.GetDelegateForFunctionPointer(Find("GetRecords"), typeof(ReadFunction));
                close = (NoArguments)Marshal.GetDelegateForFunctionPointer(Find("CloseDevice"), typeof(NoArguments));
                ready = true;
            }
            finally
            {
                if (!ready)
                    library.Dispose();
            }
        }

        public int OpenDevice()
        {
            EnsureLoaded();
            return open();
        }

        public int GetDeviceId()
        {
            EnsureLoaded();
            return identity();
        }

        public int Verify(int value)
        {
            EnsureLoaded();
            return verify(value);
        }

        public int GetRecords(string filename, int encryption)
        {
            EnsureLoaded();
            return read(filename, encryption);
        }

        public int CloseDevice()
        {
            EnsureLoaded();
            return close();
        }

        public void Dispose()
        {
            library.Dispose();
        }

        private void EnsureLoaded()
        {
            if (library.IsClosed)
                throw new ObjectDisposedException("NativeDevice");
        }

        private IntPtr Find(string name)
        {
            IntPtr address = GetProcAddress(library, name);
            if (address == IntPtr.Zero)
            {
                int error = Marshal.GetLastWin32Error();
                throw new Win32Exception(error, "SDK is missing required export: " + name + " (Win32 " + error + ").");
            }
            return address;
        }

        private sealed class LibraryHandle : SafeHandleZeroOrMinusOneIsInvalid
        {
            private LibraryHandle() : base(true) { }

            protected override bool ReleaseHandle()
            {
                return FreeLibrary(handle);
            }
        }

        [DllImport("kernel32.dll", CharSet = CharSet.Unicode, ExactSpelling = true, SetLastError = true)]
        private static extern LibraryHandle LoadLibraryExW(string filename, IntPtr reserved, uint flags);

        [DllImport("kernel32.dll", CharSet = CharSet.Ansi, ExactSpelling = true, SetLastError = true)]
        private static extern IntPtr GetProcAddress(LibraryHandle module, string name);

        [DllImport("kernel32.dll", ExactSpelling = true)]
        [return: MarshalAs(UnmanagedType.Bool)]
        private static extern bool FreeLibrary(IntPtr module);
    }
}
