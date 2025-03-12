// src/app/dashboard/page.tsx
import React from "react";

const DashboardPage = () => {
    return (
        <div>
            <h2 className="text-3xl font-semibold text-gray-800 mb-6">Welcome to your Dashboard</h2>

            {/* Dashboard Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                {/* Card 1 */}
                <div className="bg-white p-4 rounded-lg shadow-md hover:bg-gray-50">
                    <h3 className="text-lg font-semibold text-gray-800">Total Sales</h3>
                    <p className="text-xl font-bold text-gray-600 mt-2">$5000</p>
                </div>

                {/* Card 2 */}
                <div className="bg-white p-4 rounded-lg shadow-md hover:bg-gray-50">
                    <h3 className="text-lg font-semibold text-gray-800">New Orders</h3>
                    <p className="text-xl font-bold text-gray-600 mt-2">120</p>
                </div>

                {/* Card 3 */}
                <div className="bg-white p-4 rounded-lg shadow-md hover:bg-gray-50">
                    <h3 className="text-lg font-semibold text-gray-800">Total Users</h3>
                    <p className="text-xl font-bold text-gray-600 mt-2">350</p>
                </div>
            </div>

            {/* More Content */}
            <div className="mt-8 bg-white p-4 rounded-lg shadow-md">
                <h3 className="text-xl font-semibold text-gray-800">Recent Activity</h3>
                <ul className="mt-4 space-y-2">
                    <li className="flex justify-between text-gray-600">
                        <span>Order #1234</span>
                        <span className="text-gray-500">2 hours ago</span>
                    </li>
                    <li className="flex justify-between text-gray-600">
                        <span>User #789</span>
                        <span className="text-gray-500">5 hours ago</span>
                    </li>
                    <li className="flex justify-between text-gray-600">
                        <span>Product #456</span>
                        <span className="text-gray-500">1 day ago</span>
                    </li>
                </ul>
            </div>
        </div>
    );
};

export default DashboardPage;
